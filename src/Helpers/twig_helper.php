<?php

use Alghool\Twig\Config\Services;
use Alghool\Twig\Twig;

if (! function_exists('twig_instance')) {
    /**
     * load twig
     *
     * @return Twig
     */
    function twig_instance()
    {
        return Services::twig();
    }
}
