<?php

if (! function_exists('set_active')) {
    /**
     * Determine if a route is the currently active route.
     *
     * @param  string  $path
     * @param  string  $class
     * @return string
     */
    function set_active($path, $class = 'active')
    {
        return Request::is(config('translation.ui_url').$path) ? $class : '';
    }
}

if (! function_exists('strs_contain')) {
    /**
     * Determine whether any of the provided strings in the haystack contain the needle.
     *
     * @param  array  $haystacks
     * @param  string  $needle
     * @return bool
     */
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
    /**
     * Recursively diff two arrays.
     *
     * @param  array  $arrayOne
     * @param  array  $arrayTwo
     * @return array
     */
    function array_diff_assoc_recursive($arrayOne, $arrayTwo)
    {
        foreach ($arrayOne as $key => $value) {
            if (is_array($value) || $value instanceof Illuminate\Support\Collection) {
                if (! isset($arrayTwo[$key])) {
                    $difference[$key] = $value;
                } elseif (! (is_array($arrayTwo[$key]) || $arrayTwo[$key] instanceof Illuminate\Support\Collection)) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = array_diff_assoc_recursive($value, $arrayTwo[$key]);
                    if ($new_diff != false) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (! isset($arrayTwo[$key])) {
                $difference[$key] = $value;
            }
        }

        return ! isset($difference) ? [] : $difference;
    }
}

if (! function_exists('str_before')) {
    /**
     * Get the portion of a string before a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_before($subject, $search)
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }
}
