<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8c7c1a1801bf340898ab95fd4c0e8980
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ScssPhp\\ScssPhp\\' => 16,
            'Sabberworm\\CSS\\' => 15,
        ),
        'P' => 
        array (
            'Padaliyajay\\PHPAutoprefixer\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ScssPhp\\ScssPhp\\' => 
        array (
            0 => __DIR__ . '/..' . '/scssphp/scssphp/src',
        ),
        'Sabberworm\\CSS\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabberworm/php-css-parser/src',
        ),
        'Padaliyajay\\PHPAutoprefixer\\' => 
        array (
            0 => __DIR__ . '/..' . '/padaliyajay/php-autoprefixer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8c7c1a1801bf340898ab95fd4c0e8980::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8c7c1a1801bf340898ab95fd4c0e8980::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8c7c1a1801bf340898ab95fd4c0e8980::$classMap;

        }, null, ClassLoader::class);
    }
}
