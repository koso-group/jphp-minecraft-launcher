<?php

$launchOptions = array(
    'url' => array (
        'meta' =>       'https://launchermeta.mojang.com',
        'resource' =>   'https://resources.download.minecraft.net'
    ),
    //'javaPath' => 'javaw',
    'launcherPath' =>   './minecraft',
    'version' => array(
        //'jsonDownload' => false,
        'number' =>     '1.16.5',
        'type' =>       'release'
    ),
    'auth' => array(
        'username' =>   'meatsuko',
        'uuid' =>       'meatsuko',
        'token' =>      'shalala',
        'type' =>       'trophy',
    ),
    //'detached' => false,
    'memory' => array(
        'min' =>        '8G',
        'max' =>        '32G'
    )
);

(new TrophyLauncher()).launch($launchOptions);