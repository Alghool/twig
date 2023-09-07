<?php

namespace Alghool\Twig\Config;

use CodeIgniter\Config\BaseService;
use Alghool\Twig\Config\Twig as TwigConfig;

use Alghool\Twig\Twig;

class Services extends BaseService
{
    public static function twig(?TwigConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('twig', $config);
        }

        $config ??= config('Twig');

        return new Twig($config);
    }
}
