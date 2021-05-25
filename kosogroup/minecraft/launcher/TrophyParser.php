<?php

namespace kosogroup\minecraft\launcher;

use kosogroup\minecraft\launcher\TrophyLauncher;

class TrophyParser 
{
    private $_launcher;

    private $_url = array();

    function __construct(TrophyLauncher $launcher)
    {
        $this->_launcher = $launcher;

        $this->_url = array(
            'meta' => 'https://launchermeta.mojang.com'
        );

    }

    public function getMinecraftJSON($version = null)
    {
        if(!$version) $version = $this->_launcher->getLaunchOptions()['version'];
        //if(!$version) $version = ($this->_launcher->getLaunchOptions())['version']; // синтасический парсер в DN к такому не говтов
        
        $meta = json_decode(file_get_contents($this->_url['meta'] . '/mc/game/version_manifest.json'), true);
        foreach($meta['versions'] as $metaVersion)
            if($metaVersion['id'] == $version['number'])
                return json_decode(file_get_contents($metaVersion['url']), true);
            
        return null;
    }

    public function getLaunchArguments($minecraftJSON, $updateResults)
    {
        $launchOptions = $this->_launcher->getLaunchOptions();

        $classpath = null;
        foreach($updateResults['collections']['libraries'] as $library)
            $classpath[] = $updateResults['dir']['libraries'] . '/' . $library['path'];

        $classpath[] = $updateResults['dir']['version'] . '/' . $updateResults['collections']['version'][0]['path'];
        $classpath = implode(';', $classpath);

        $replaceFields = array(
            '${auth_player_name}' => $launchOptions['auth']['username'],
            '${version_name}' => $launchOptions['version']['number'],
            '${game_directory}' => 'version/' . $launchOptions['version']['number'],
            '${assets_root}' => $updateResults['dir']['assets'],
            '${assets_index_name}' => $minecraftJSON['assetIndex']['id'],
            '${auth_uuid}' => $launchOptions['auth']['uuid'],
            '${auth_access_token}' => $launchOptions['auth']['token'],
            '${user_type}' => $launchOptions['auth']['type'],
            '${version_type}' => $launchOptions['version']['type'],


            '-Djava.library.path=${natives_directory}' => '-Djava.library.path=' . $updateResults['dir']['natives'] . '',
            '-Dminecraft.launcher.brand=${launcher_name}' => '-Dminecraft.launcher.brand="TrophyLauncher"',
            '-Dminecraft.launcher.version=${launcher_version}' => '-Dminecraft.launcher.version="V:4.2.2RC-1"',
            '${classpath}' => $classpath
        );

        

        $result = array('jvm' => [], 'game' => []);

        //wtf: invalid heap size
        //$result['jvm'][] = '-Xms' . $launchOptions['memory']['min'];
        //$result['jvm'][] = '-Xmx' . $launchOptions['memory']['max'];

        foreach($minecraftJSON['arguments']['jvm'] as $argument)
        {
            $allow = true;
            $os = 'windows';

            if(is_array($argument))
            {
                $allow = false;
                if(is_array($argument['rules'])) foreach($argument['rules'] as $rule)
                {
                    if(isset($rule['os']))
                    {
                        if(isset($rule['os']['name'])) 
                            if($rule['os']['name'] == $os) $allow = $action;

                        if(isset($rule['os']['arch'])) $allow = false;
                        if(isset($rule['os']['version'])) $allow = false;
                    }
                }

                $argument = $argument['value'];
            }

            if(array_key_exists($argument, $replaceFields)) $argument = $replaceFields[$argument];

            if($allow) $result['jvm'][] = $argument;
        }

        $result['jvm'][] = $minecraftJSON['mainClass'];

        foreach($minecraftJSON['arguments']['game'] as $argument)
        {
            $allow = true;

            if(is_array($argument))
            {
                $allow = false;
            }

            if(array_key_exists($argument, $replaceFields)) $argument = $replaceFields[$argument];

            if($allow) $result['game'][] = $argument;
        }


        return $result;
    }
}