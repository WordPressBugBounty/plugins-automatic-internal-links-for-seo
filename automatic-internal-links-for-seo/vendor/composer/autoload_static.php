<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit902146bc62f26ff98317ab6a3c99c69d
{
    public static $prefixLengthsPsr4 = array (
        'h' => 
        array (
            'html_changer\\testing\\' => 21,
            'html_changer\\' => 13,
        ),
        'P' => 
        array (
            'Pagup\\AutoLinks\\Traits\\' => 23,
            'Pagup\\AutoLinks\\Core\\' => 21,
            'Pagup\\AutoLinks\\Controllers\\' => 28,
            'Pagup\\AutoLinks\\Bootstrap\\' => 26,
            'Pagup\\AutoLinks\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'html_changer\\testing\\' => 
        array (
            0 => __DIR__ . '/..' . '/friedolinfoerder/html-changer/spec',
        ),
        'html_changer\\' => 
        array (
            0 => __DIR__ . '/..' . '/friedolinfoerder/html-changer/src',
        ),
        'Pagup\\AutoLinks\\Traits\\' => 
        array (
            0 => __DIR__ . '/../..' . '/admin/traits',
        ),
        'Pagup\\AutoLinks\\Core\\' => 
        array (
            0 => __DIR__ . '/../..' . '/admin/core',
        ),
        'Pagup\\AutoLinks\\Controllers\\' => 
        array (
            0 => __DIR__ . '/../..' . '/admin/controllers',
        ),
        'Pagup\\AutoLinks\\Bootstrap\\' => 
        array (
            0 => __DIR__ . '/../..' . '/bootstrap',
        ),
        'Pagup\\AutoLinks\\' => 
        array (
            0 => __DIR__ . '/../..' . '/admin',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit902146bc62f26ff98317ab6a3c99c69d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit902146bc62f26ff98317ab6a3c99c69d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit902146bc62f26ff98317ab6a3c99c69d::$classMap;

        }, null, ClassLoader::class);
    }
}
