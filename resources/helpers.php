<?php

if (!function_exists('set_active')) {
    function set_active($path, $class = 'active')
    {
        return Request::is(config('translation.ui_url') . $path) ? $class : '';
    }
}
