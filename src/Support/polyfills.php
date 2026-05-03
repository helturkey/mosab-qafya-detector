<?php

declare(strict_types=1);

if (! function_exists('mb_str_split')) {
    /**
     * @return list<string>
     */
    function mb_str_split(string $string, int $length = 1, ?string $encoding = null): array
    {
        if ($length < 1) {
            throw new ValueError('The length must be greater than 0');
        }

        /** @var list<string>|false $chars */
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return str_split($string, $length);
        }

        if ($length === 1) {
            return $chars;
        }

        $chunks = [];
        for ($i = 0; $i < count($chars); $i += $length) {
            $chunks[] = implode('', array_slice($chars, $i, $length));
        }

        return $chunks;
    }
}
