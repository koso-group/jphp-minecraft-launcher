<?php

$launchOptions = array(
    'url' => array (
        'meta' =>       'https://launchermeta.mojang.com',
        'resource' =>   'https://resources.download.minecraft.net'
    ),            
    'javaPath' =>       'java', //если не указывать внешнюю JAVA то будет тянутся JAVA из DN (во всяком случае в среде разработки было так)
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
    ///* Бывает JAVA ругается на Heap Size
    'memory' => array(
        'min' =>        '8G',
        'max' =>        '32G'
    ),
    //*/
    'eventEmiter' =>    function($target, $meta, $thread = null)
    {
        switch($target)
        {
            case "downloadUpdateAA": 
                //uiLater(function() use ($this, $meta)
                //{
                //    $this->labelStatus->text = $meta['stage'] . '@' . $meta['target'] . ' (' . $meta['current'] . '/' . $meta['of'] . ')';
                //});
                break;
            case "_download": break;
            case "_downloadCallback": 
                //uiLater(function() use ($this, $meta)
                //{
                //   $this->labelDescription->text = 'Загрузка... ( ' . $meta['name'] . ' | ' . $meta['progress'] . ' of ' . $meta['total'] . ')';
                //   $this->progressDownloadFile->progress = ($meta['progress'] / ($meta['total'] / 100));
                //});
                break;
            case "_checkHash":
                //uiLater(function() use ($this, $meta)
                //{                           
                //    $fi = explode('/', $meta['file']);
                //    $this->labelDescription->text = 'Проверка файлов... ( ' . array_pop($fi) . ' | ' . $meta['calculated_hash'] . ' of ' . $meta['hash'] . ')';
                //});
                break;

            case "std::out":
            case "err::out": 
                //uiLater(function() use ($this, $meta)
                //{    
                    //тут вывод в консоль можно выполнить                       
                //    var_dump($meta);
                //});
                break;                       



        }
    }
); 


$launcher = (new TrophyLauncher())->launch($launchOptions);  