<?php

if (! function_exists('set_active')) {
    function set_active($path, $class = 'active')
    {
        return Request::is(config('translation.ui_url').$path) ? $class : '';
    }
}

if (! function_exists('strs_contain')) {
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

if (! function_exists('array_diff_assoc_recursive')) {
    function array_diff_assoc_recursive($arrayOne, $arrayTwo)
    {
        foreach ($arrayOne as $key => $value) {
            if (is_array($value)) {
                if (! isset($arrayTwo[$key])) {
                    $difference[$key] = $value;
                } elseif (! is_array($arrayTwo[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = array_diff_assoc_recursive($value, $arrayTwo[$key]);
                    if ($new_diff != false) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (! isset($arrayTwo[$key]) || $arrayTwo[$key] != $value) {
                $difference[$key] = $value;
            }
        }

        return ! isset($difference) ? false : $difference;
    }
}
