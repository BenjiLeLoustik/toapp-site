<?php

namespace App\Controller;

use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

#[Route(path: '/preference', name: 'preference_')]
class PreferenceController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'APP_DESIGN_THEMES')]
        public string $availableThemes,
        #[Autowire(env: 'APP_DESIGN_ACCENTS')]
        public string $availableAccents,
        #[Autowire(env: 'APP_DESIGN_SCALES')]
        public string $availableScales,
        #[Autowire(env: 'APP_SESSION_THEME')]
        public string $sessionTheme,
        #[Autowire(env: 'APP_SESSION_ACCENT')]
        public string $sessionAccent,
        #[Autowire(env: 'APP_SESSION_SCALE')]
        public string $sessionScale,
    ) {
    }

    #[Route(path: '/api/change/theme/{theme}', name: 'api_change_theme', methods: ['GET'])]
    public function api_changeTheme(
        string $theme
    ): JsonResponse {
        if (in_array($theme, json_decode($this->availableThemes), true)) {
            $this->getSession()->set($this->sessionTheme, $theme);

            return $this->json([
                'success' => true,
                'theme' => $this->getSession()->get($this->sessionTheme),
            ]);
        }

        return $this->json([
            'success' => false,
        ]);
    }

    #[Route(path: '/api/change/accent/{accent}', name: 'api_change_accent', methods: ['GET'])]
    public function api_changeAccent(string $accent): JsonResponse
    {
        if (in_array($accent, json_decode($this->availableAccents), true)) {
            $this->getSession()->set($this->sessionAccent, $accent);

            return $this->json([
                'success' => true,
                'accent' => $this->getSession()->get($this->sessionAccent),
            ]);
        }

        return $this->json([
            'success' => false,
        ]);
    }

    #[Route(path: '/api/change/scale/{scale}', name: 'api_change_scale', methods: ['GET'])]
    public function api_changeScale(string $scale): JsonResponse
    {
        if (in_array($scale, json_decode($this->availableScales), true)) {
            $this->getSession()->set($this->sessionScale, $scale);

            return $this->json([
                'success' => true,
                'scale' => $this->getSession()->get($this->sessionScale),
            ]);
        }

        return $this->json([
            'success' => false,
        ]);
    }

    #[Route(path: '/api/change/locale/{locale}', name: 'api_change_locale', methods: ['GET'])]
    public function api_changeLocale(string $locale, TranslatorInterface $translator): JsonResponse
    {
        if ($translator->matchLocale($locale)) {
            $translator->setLocale($locale);

            return $this->json(['success' => true]);
        }

        return $this->json([
            'success' => false,
        ]);
    }

}