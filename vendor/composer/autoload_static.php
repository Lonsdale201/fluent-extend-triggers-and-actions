<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3ea70dd8dcd029760883f446085a075a
{
    public static $prefixLengthsPsr4 = array (
        'H' => 
        array (
            'HelloWP\\FluentExtendTriggers\\Triggers\\' => 38,
            'HelloWP\\FluentExtendTriggers\\SmartCodes\\' => 40,
            'HelloWP\\FluentExtendTriggers\\JEModules\\' => 39,
            'HelloWP\\FluentExtendTriggers\\Includes\\' => 38,
            'HelloWP\\FluentExtendTriggers\\Actions\\' => 37,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'HelloWP\\FluentExtendTriggers\\Triggers\\' => 
        array (
            0 => __DIR__ . '/../..' . '/triggers',
        ),
        'HelloWP\\FluentExtendTriggers\\SmartCodes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/smartcodes',
        ),
        'HelloWP\\FluentExtendTriggers\\JEModules\\' => 
        array (
            0 => __DIR__ . '/../..' . '/jemodules',
        ),
        'HelloWP\\FluentExtendTriggers\\Includes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
        'HelloWP\\FluentExtendTriggers\\Actions\\' => 
        array (
            0 => __DIR__ . '/../..' . '/actions',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3ea70dd8dcd029760883f446085a075a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3ea70dd8dcd029760883f446085a075a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3ea70dd8dcd029760883f446085a075a::$classMap;

        }, null, ClassLoader::class);
    }
}
