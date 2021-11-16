# Minecraft Launcher for JPHP DevelNext
## jphp-minecraft-launcher

Minecraft Launcher modeule for parse and run classic version.json from Mojang LAUNCHERMETA 

## Featuers  ✨Magic ✨
- Run Minecraft
- Spoofing META URL's
- Downloads and Update resources


## Getting Started  ✨Magic ✨
 - add "ZIP 1.0" package to project
 - insert files to 'kosogroup/minecraft/launcher/core' to SRC root
 - copy this code
 - Enjoy!
```php
<?php

use kosogroup\minecraft\launcher\core\TrophyLauncher;

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

(new TrophyLauncher())->launch($launchOptions);
```