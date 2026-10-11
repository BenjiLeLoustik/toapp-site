<?php

declare(strict_types=1);

namespace App\Admin\Contract;

use App\Admin\Exception\AdminException;
use Closure;
use NeoPHP\Component\Form\Contract\FormInterface;
use NeoPHP\Component\Form\FormManagerInterface;
use NeoPHP\Component\Form\Type\FormType;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\NeoAdmin\Contract\AbstractAdminController;
use NeoPHP\NeoAdmin\Page\Block\Table;
use NeoPHP\NeoAdmin\Page\Column;
use NeoPHP\Package\Translation\TranslationManagerInterface;

abstract class AbstractToolController extends AbstractAdminController
{
    protected function request(): Request
    {
        return $this->get(Request::class);
    }

    protected function locales(): array
    {
        return $this->has(TranslationManagerInterface::class) ? array_values($this->get(TranslationManagerInterface::class)->getLocales()) : ['en'];
    }

    protected function defaultLocale(): string
    {
        return $this->has(TranslationManagerInterface::class) ? $this->get(TranslationManagerInterface::class)->getDefaultLocale() : 'en';
    }

    protected function locale(): string
    {
        $locale = $this->request()->query->getString('locale', $this->defaultLocale());

        return in_array($locale, $this->locales(), true) ? $locale : $this->defaultLocale();
    }

    protected function localeName(string $locale): string
    {
        return class_exists(\Locale::class) ? ucfirst(\Locale::getDisplayName($locale, $locale)) : strtoupper($locale);
    }

    protected function localeChoices(): array
    {
        $choices = [];

        foreach ($this->locales() as $locale) {
            $choices[$this->localeName($locale)] = $locale;
        }

        return $choices;
    }

    protected function localeTabs(): array
    {
        return array_flip($this->localeChoices());
    }

    protected function createAdminForm(string $name, array $data, array $fields): FormInterface
    {
        $form = $this->get(FormManagerInterface::class)->createNamed($name, FormType::class, $data);

        foreach ($fields as $field => [$type, $options]) {
            $form->add($field, $type, $options);
        }

        return $form;
    }

    protected function submitted(FormInterface $form): ?array
    {
        $form->handleRequest($this->request());

        return $form->isSubmitted() && $form->isValid() ? (array) $form->getData() : null;
    }

    protected function status(FormInterface ...$forms): int
    {
        foreach ($forms as $form) {
            if ($form->isSubmitted()) {
                return 422;
            }
        }

        return 200;
    }

    protected function attempt(Closure $action, string|Closure $success): bool
    {
        try {
            $result = $action();
        } catch (AdminException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        $this->addFlash('success', $success instanceof Closure ? $success($result) : $success);

        return true;
    }

    protected function handlePost(string $token, Closure $action, string|Closure $success): bool
    {
        $value = $this->request()->request->get('_token');

        if (!$this->isCsrfTokenValid($token, is_string($value) ? $value : null)) {
            $this->addFlash('error', 'The security token is invalid, please try again.');

            return false;
        }

        return $this->attempt($action, $success);
    }

    protected function findOr404(Closure $finder): mixed
    {
        try {
            return $finder();
        } catch (AdminException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }
    }

    protected function details(array $values, ?string $title = null, ?string $icon = null): Table
    {
        $rows = [];

        foreach ($values as $key => $value) {
            $rows[] = ['field' => ucfirst(str_replace('_', ' ', (string) $key)), 'value' => is_bool($value) ? ($value ? 'yes' : 'no') : (string) ($value ?? '-')];
        }

        return new Table($title, $rows, [Column::text('field', 'Field'), Column::text('value', 'Value')], $icon);
    }

    protected function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}