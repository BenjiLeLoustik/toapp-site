<?php

namespace App\Controller;

use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
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

    #[Route(path: '/api/change/theme', name: 'api_change_theme', methods: ['GET'])]
    public function api_changeTheme(Request $request): JsonResponse {
        $theme = $request->query->get('theme');

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

    #[Route(path: '/api/change/accent', name: 'api_change_accent', methods: ['GET'])]
    public function api_changeAccent(Request $request): JsonResponse {
        $accent = $request->query->get('accent');

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

    #[Route(path: '/api/change/scale', name: 'api_change_scale', methods: ['GET'])]
    public function api_changeScale(Request $request): JsonResponse
    {
        $scale = $request->query->get('scale');

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

    #[Route(path: '/api/change/locale', name: 'api_change_locale', methods: ['GET'])]
    public function api_changeLocale(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $locale = $request->query->get('locale');

        if ($translator->matchLocale($locale)) {
            $this->switchLocale($locale);

            return $this->json(['success' => true]);
        }

        return $this->json([
            'success' => false,
        ]);
    }

}