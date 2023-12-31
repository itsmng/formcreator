<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit85f215f54acb58e8c5069d763fc0c0de
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

        spl_autoload_register(array('ComposerAutoloaderInit85f215f54acb58e8c5069d763fc0c0de', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit85f215f54acb58e8c5069d763fc0c0de', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit85f215f54acb58e8c5069d763fc0c0de::getInitializer($loader));

        $loader->setApcuPrefix('st5erVstiIwAzQ0H0X68u');
        $loader->register(true);

        return $loader;
    }
}
