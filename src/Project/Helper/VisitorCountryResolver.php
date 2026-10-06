<?php

declare(strict_types=1);

namespace App\Project\Helper;

use NeoPHP\Component\Http\Request\Request;

class VisitorCountryResolver
{
    public const IGNORED = ['XX', 'T1', 'EU', 'AP'];

    public function resolve(Request $request): ?string
    {
        return $this->fromCloudflare($request) ?? $this->fromLanguage($request);
    }

    private function fromCloudflare(Request $request): ?string
    {
        $country = strtoupper(trim((string) $request->headers->get('CF-IPCountry')));

        return $this->valid($country) ? $country : null;
    }

    private function fromLanguage(Request $request): ?string
    {
        $header = (string) $request->headers->get('Accept-Language');

        foreach (explode(',', $header) as $part) {
            if (preg_match('/^\s*[a-z]{2,3}[-_]([a-z]{2})\b/i', $part, $match) === 1) {
                $country = strtoupper($match[1]);

                if ($this->valid($country)) {
                    return $country;
                }
            }
        }

        return null;
    }

    private function valid(string $country): bool
    {
        return preg_match('/^[A-Z]{2}$/', $country) === 1 && !in_array($country, self::IGNORED, true);
    }
}