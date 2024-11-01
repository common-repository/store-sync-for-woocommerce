<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit810c0eb3410b4eb21489e3693e940138
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPQueryBuilder\\' => 15,
        ),
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPQueryBuilder\\' => 
        array (
            0 => __DIR__ . '/..' . '/stephenharris/wp-query-builder/src',
        ),
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit810c0eb3410b4eb21489e3693e940138::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit810c0eb3410b4eb21489e3693e940138::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
