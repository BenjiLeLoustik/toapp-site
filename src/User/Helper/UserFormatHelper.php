<?php

declare(strict_types=1);

namespace App\User\Helper;

use App\Entity\User;
use App\Entity\UserPreference;
use App\User\Enum\UserDateFormatEnum;
use App\User\Enum\UserNumberFormatEnum;
use NeoPHP\Package\Security\Contract\SecurityInterface;

class UserFormatHelper
{
    public const COMPACT_UNITS = [
        1_000_000_000 => 'B',
        1_000_000 => 'M',
        1_000 => 'k',
    ];

    private ?UserPreference $preference = null;

    private bool $loaded = false;

    public function __construct(
        private SecurityInterface $security,
        private UserSettingsHelper $settings,
    ) {
    }

    public function formatDate(mixed $date, bool $withTime = false): string
    {
        $date = $this->toDate($date);

        if ($date === null) {
            return '';
        }

        return $this->dateFormat()->format($date->setTimezone($this->timezone()), $withTime);
    }

    public function formatNumber(mixed $number, int $decimals = 0): string
    {
        if (!$this->isNumber($number)) {
            return '';
        }

        return $this->numberFormat()->format((float) $number, $decimals);
    }

    public function formatCompact(mixed $number, int $precision = 1): string
    {
        if (!$this->isNumber($number)) {
            return '';
        }

        $number = $number + 0;
        $absolute = abs($number);

        foreach (self::COMPACT_UNITS as $threshold => $suffix) {
            if ($absolute >= $threshold) {
                return $this->trimDecimals(number_format($number / $threshold, $precision, '.', '')) . $suffix;
            }
        }

        return is_int($number) ? (string) $number : $this->trimDecimals(number_format($number, $precision, '.', ''));
    }

    public function dateFormat(): UserDateFormatEnum
    {
        return $this->preference()?->getDateFormat() ?? UserDateFormatEnum::default();
    }

    public function numberFormat(): UserNumberFormatEnum
    {
        return $this->preference()?->getNumberFormat() ?? UserNumberFormatEnum::default();
    }

    public function timezone(): \DateTimeZone
    {
        $timezone = $this->preference()?->getTimezone();

        if ($timezone !== null && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            return new \DateTimeZone($timezone);
        }

        return new \DateTimeZone(date_default_timezone_get());
    }

    private function trimDecimals(string $value): string
    {
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return str_replace('.', $this->numberFormat()->decimalSeparator(), $value);
    }

    private function isNumber(mixed $number): bool
    {
        return $number !== null && $number !== '' && is_numeric($number);
    }

    private function preference(): ?UserPreference
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $user = $this->security->getUser();

            $this->preference = $user instanceof User ? $this->settings->getPreference($user) : null;
        }

        return $this->preference;
    }

    private function toDate(mixed $date): ?\DateTimeImmutable
    {
        return match (true) {
            $date instanceof \DateTimeImmutable => $date,
            $date instanceof \DateTime => \DateTimeImmutable::createFromMutable($date),
            is_int($date) => (new \DateTimeImmutable())->setTimestamp($date),
            is_string($date) && $date !== '' => $this->parse($date),
            default => null,
        };
    }

    private function parse(string $date): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($date);
        } catch (\Exception) {
            return null;
        }
    }
}