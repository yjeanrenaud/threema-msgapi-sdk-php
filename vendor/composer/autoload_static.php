<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit89d2c4069c61597ffd30a4fbdc0d4ed3
{
    public static $files = array (
        'd4866de86020f8eba594f63fb957cb26' => __DIR__ . '/../..' . '/src/Salt/Curve25519/Curve25519.php',
        'd36eb1fc2ba9abd312b90953c436b45c' => __DIR__ . '/../..' . '/src/Salt/FieldElement.php',
        '6c49c0cb70ab0e411c388f368f7095e5' => __DIR__ . '/../..' . '/src/Salt/Salt.php',
        '9e06a77e6ddd01a72d22e6ad45f3651e' => __DIR__ . '/../..' . '/src/Salt/SaltException.php',
        'b0796b86d25b2ce7da4201af7da3a392' => __DIR__ . '/../..' . '/src/Salt/Poly1305/Poly1305.php',
        'd25c8aa6dfa1074b52a484d9e0722e02' => __DIR__ . '/../..' . '/src/Salt/Salsa20/Salsa20.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Threema\\MsgApi\\' => 15,
            'Threema\\Console\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Threema\\MsgApi\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/MsgApi',
            1 => __DIR__ . '/../..' . '/src/MsgApi',
        ),
        'Threema\\Console\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Console',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit89d2c4069c61597ffd30a4fbdc0d4ed3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit89d2c4069c61597ffd30a4fbdc0d4ed3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit89d2c4069c61597ffd30a4fbdc0d4ed3::$classMap;

        }, null, ClassLoader::class);
    }
}
