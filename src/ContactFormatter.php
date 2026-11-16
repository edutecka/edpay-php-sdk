<?php

namespace EDPay;

use InvalidArgumentException;

/** Configurable phone normalization and validation helpers. */
final class ContactFormatter
{
    private static function parse(string $value, array $options = []): array
    {
        $countryCode = (string) ($options['country_calling_code'] ?? '258');
        if (strpos($countryCode, '+') === 0) {
            $countryCode = substr($countryCode, 1);
        }
        $nationalLength = (int) ($options['national_length'] ?? 9);
        if (!preg_match('/^[1-9][0-9]{0,2}$/', $countryCode)
            || $nationalLength < 1 || strlen($countryCode) + $nationalLength > 15) {
            throw new InvalidArgumentException('Invalid phone number rules.');
        }

        $input = trim($value);
        if ($input === '' || !preg_match('/^\+?[0-9\s().-]+$/', $input)
            || substr_count($input, '(') !== substr_count($input, ')')) {
            throw new InvalidArgumentException('Phone number contains unsupported characters.');
        }
        $international = strpos($input, '+') === 0;
        $digits = preg_replace('/\D/', '', $input);
        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
            $international = true;
        }
        if (!$international && strpos($digits, $countryCode) === 0 && strlen($digits) > $nationalLength) {
            $international = true;
        }
        if ($international) {
            if (strpos($digits, $countryCode) !== 0) {
                throw new InvalidArgumentException('Phone number does not use the configured country calling code.');
            }
            $digits = substr($digits, strlen($countryCode));
        }
        if (strlen($digits) !== $nationalLength || !ctype_digit($digits)) {
            throw new InvalidArgumentException('Phone number does not match the configured national length.');
        }
        $prefixes = $options['national_prefixes'] ?? [];
        foreach ($prefixes as $prefix) {
            if (!is_string($prefix) || $prefix === '' || !ctype_digit($prefix)) {
                throw new InvalidArgumentException('national_prefixes must contain digit strings.');
            }
        }
        if ($prefixes && !array_filter($prefixes, static fn ($prefix) => strpos($digits, $prefix) === 0)) {
            throw new InvalidArgumentException('Phone number does not match the configured national prefixes.');
        }
        return [$countryCode, $digits];
    }

    public static function normalize(string $value, array $options = []): string
    {
        [$countryCode, $national] = self::parse($value, $options);
        $output = $options['output'] ?? 'international';
        if (!in_array($output, ['international', 'country-code', 'national'], true)) {
            throw new InvalidArgumentException("output must be 'international', 'country-code', or 'national'.");
        }
        if ($output === 'national') return $national;
        return ($output === 'country-code' ? $countryCode : '+' . $countryCode) . $national;
    }

    public static function addCountryPrefix(string $value, array $options = []): string
    {
        $options['output'] = 'international';
        return self::normalize($value, $options);
    }

    public static function removeCountryPrefix(string $value, array $options = []): string
    {
        $options['output'] = 'national';
        return self::normalize($value, $options);
    }

    public static function format(string $value, array $options = []): string
    {
        [$countryCode, $national] = self::parse($value, $options);
        $groups = $options['group_sizes'] ?? ($countryCode === '258' && strlen($national) === 9 ? [2, 3, 4] : [strlen($national)]);
        $parts = [];
        $offset = 0;
        foreach ($groups as $size) {
            if (!is_int($size) || $size < 1) throw new InvalidArgumentException('group_sizes must contain positive integers.');
            if ($offset >= strlen($national)) break;
            $parts[] = substr($national, $offset, $size);
            $offset += $size;
        }
        if ($offset < strlen($national)) $parts[] = substr($national, $offset);
        return '+' . $countryCode . ' ' . implode(' ', $parts);
    }

    public static function isValid(string $value, array $options = []): bool
    {
        try { self::parse($value, $options); return true; } catch (InvalidArgumentException $error) { return false; }
    }

    /** Return known phone rules; null means the method is non-phone or unknown. */
    public static function optionsForMethod(string $method): ?array
    {
        $key = strtolower(str_replace([' ', '_', '-'], '', trim($method)));
        if ($key === 'emola') return ['country_calling_code' => '258', 'national_length' => 9, 'national_prefixes' => ['86', '87'], 'output' => 'national'];
        if ($key === 'mpesa') return ['country_calling_code' => '258', 'national_length' => 9, 'national_prefixes' => ['84', '85'], 'output' => 'country-code'];
        return null;
    }
}
