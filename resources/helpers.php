<?php

if (!function_exists('set_active')) {
    function set_active($path, $class = 'active')
    {
        return Request::is(config('translation.ui_url') . $path) ? $class : '';
    }
}

if (!function_exists('strs_contain')) {
    function strs_contain($haystacks, $needle)
    {
        $haystacks = (array) $haystacks;

        foreach ($haystacks as $haystack) {
            if (str_contains(strtolower($haystack), strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
