<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit902146bc62f26ff98317ab6a3c99c69d
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit902146bc62f26ff98317ab6a3c99c69d', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit902146bc62f26ff98317ab6a3c99c69d', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit902146bc62f26ff98317ab6a3c99c69d::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
