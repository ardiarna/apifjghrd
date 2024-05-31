<?php

if(!function_exists('public_path')) {
    function public_path($path = '') {
        return app()->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
