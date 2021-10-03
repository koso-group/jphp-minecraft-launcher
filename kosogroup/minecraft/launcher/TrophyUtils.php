<?php

namespace kosogroup\minecraft\launcher;

//devenext uses
use std;
use php\compress\ZipFile;
use php\io\IOException;

use kosogroup\minecraft\launcher\TrophyLauncher;
use kosogroup\minecraft\launcher\TrophyParser;

class TrophyUtils 
{
    private $_launcher;
    private $_parser;

    private $_url = array();

    function __construct(TrophyLauncher $launcher, TrophyParser $parser)
    {
        $this->_launcher = $launcher;
        $this->_parser = $parser;

        $this->_url = array(
            'resource' => 'https://resources.download.minecraft.net'
        );
    }

    private function _resolveDir($dir)
    {
        if(!fs::exists($dir)) fs::makeDir($dir);
        return fs::abs($dir);
    }

    private function _fetchRule($library) : bool
    {
        $allow = false;
        $os = 'windows';

        if(isset($library['rules'])) foreach($library['rules'] as $rule)
        {
            $action = ($rule['action'] == 'allow');

            if(isset($rule['os']))
            {
                if($rule['os']['name'] == $os) $allow = $action;
            }
            else $allow = $action;
            
        }
        else $allow = true;

        return $allow;
    }

    public function downloadUpdate($minecraftJSON)  
    {
        $launchOptions = $this->_launcher->getLaunchOptions();

        //
        //

        $dirVersion = $launchOptions['launcherPath'] . '/' . 'versions/' . $launchOptions['version']['number'];
        $dirVersion = $this->_resolveDir($dirVersion);

        //if($launchOptions['version']['jsonDownload']) {}
        $artifact = $minecraftJSON['downloads']['client'];
        $artifact['path'] = $launchOptions['version']['number'] . '.jar';

        $collections['version'] = $this->downloadUpdateAA([$artifact], 'artifacts-jar', $dirVersion);

        //
        //
        //
        //
        //

        $dirNatives = $launchOptions['launcherPath'] . '/' . 'natives/' . $minecraftJSON['id'];
        $dirNatives = $this->_resolveDir($dirNatives);

        $natives = null;
        foreach($minecraftJSON['libraries'] as $library)
        {
            if(!isset($library['downloads']) || !isset($library['downloads']['classifiers'])) continue;
            if(!$this->_fetchRule($library)) continue;

            // only for windows // to-do fix to mac/linux
            $natives[] = $library['downloads']['classifiers']['natives-windows'];
        }

        $collections['natives'] = $this->downloadUpdateAA($natives, 'natives', $dirNatives, true);

        //
        //
        //
        //
        //

        $dirLibraries = $launchOptions['launcherPath'] . '/' . 'libraries/';
        $dirLibraries = $this->_resolveDir($dirLibraries);

        $libraries = null;
        foreach($minecraftJSON['libraries'] as $library)
        {
            if(!isset($library['downloads']) || !isset($library['downloads']['artifact'])) continue;
            if(!$this->_fetchRule($library)) continue;

            $libraries[] = $library['downloads']['artifact'];
        }

        $collections['libraries'] = $this->downloadUpdateAA($libraries, 'libraries', $dirLibraries);

        //
        //
        //
        //
        //

        $dirAssets = $launchOptions['launcherPath'] . '/' . 'assets/';
        $dirAssets = $this->_resolveDir($dirAssets);

        $dirAssetsIndexes = $dirAssets . '/' . 'indexes/';
        $this->_resolveDir($dirAssetsIndexes);

        $indexPath = $dirAssetsIndexes . $minecraftJSON['assetIndex']['id'] . '.json';
        $assetIndexJSON = file_get_contents($minecraftJSON['assetIndex']['url']);

        if(!fs::exists($indexPath)) file_put_contents($indexPath, $assetIndexJSON);
        

        $assetIndexJSON = json_decode($assetIndexJSON, true);
        
        $assets = null;
        foreach($assetIndexJSON['objects'] as $assetName => $assetData)
        {   
            
            $assets[] = array(
                'path' => 'objects/' . substr($assetData['hash'], 0, 2) . '/' . $assetData['hash'],
                'sha1' => $assetData['hash'],
                'size' => $assetData['size'],
                'url' => $this->_url['resource'] . '/' . substr($assetData['hash'], 0, 2) . '/' . $assetData['hash']
            );
        }

        $collections['assets'] = $this->downloadUpdateAA($assets, 'assets', $dirAssets);

        //
        //

        return array(
            'dir' => array(
                'version' => $dirVersion,
                'natives' => $dirNatives,
                'libraries' => $dirLibraries,
                'assets' => $dirAssets
            ),
            'collections' => $collections
        );
    }

    public function downloadUpdateAA($collections, $target, $dir, $unzip = false)
    {
        $meta_current = 0;
        foreach($collections as $downloadable)
        {
            $meta_current++;

            if(!isset($downloadable)) return;

            $explode = explode('/', $downloadable['path']);
            $name = array_pop($explode);
            $path =  $dir . '/' . implode('/', $explode);

            $filePath = $dir . '/' . $downloadable['path'];
            $dd = false;
            if(!fs::exists($filePath))
            {
                $meta_stage = 'downloading';
                $this->_download($downloadable['url'], $path, $name, true, $target);
                $dd = true;
            }
            else
            {
                if(!$this->_checkHash($filePath, $downloadable['sha1']))
                {
                    $meta_stage = 'downloading/invalid_hashing';
                    $this->_download($downloadable['url'], $path, $name, true, $target);
                    $dd = true;
                }
            }
                

            if($unzip && $dd)
            {   
                $meta_stage = 'unzipping';

                try
                {
                    new ZipFile($filePath)->unpack($dir);
                }
                catch(IOException $exception)
                {

                }
                
            }

            $meta_stage = 'done';

            $_meta = array(
                'target' => $target,
                'stage' => $meta_stage,
                'current' => $meta_current,
                'of' => count($collections)
            );
            $this->_launcher->__eventEmit('downloadUpdateAA', $_meta);
        }

        


        return $collections;
    }

    private function _download($link, $dir, $name, $retry, $target)
    {

        $_totalBytes = 0;
        $_receivedBytes = 0;


        $url = new URL($link);
        $connection = $url->openConnection();
        
        $_totalBytes = $connection->contentLength;

        $inputStream = $url->openStream();

        $buffer = null;
        $bufferSize = 8192;
        //ебаный рот этого казино
        //while($_receivedBytes = $inputStream->read())  { }
        // read(int) принимает количество скачиваемых байт, возвращает порцию данных на это самое количество
        /*while(!( $_receivedBytes >= $_totalBytes )) // wtf
        {
            // неужели было так сложно запилить человческие стримы из java, сейчас бы небыло это ебатни математической
            $math = ($_totalBytes - $_receivedBytes);
            if(!($math > $bufferSize)) $bufferSize = $math;

            //да еще хуй знает как байтбуфер клеить в PHP, вариант конечно хороший это сразу писать в файл
            $buffer[] = $inputStream->read($bufferSize);

            $_receivedBytes += $bufferSize;
        }
        //*/
        //$wtf = implode($buffer, '');

        
        $closureCallback = function ($progress, $bytes) use ($_totalBytes, $url, $name)
        {
            //////////////////////////////////////////////////???????????????????????????????????????????????????????
            $_meta = array(
                'progress' => $progress,
                'bytes' => $bytes,
                'total' => $_totalBytes,
                'url' => $url,
                'name' => $name
            );
            $this->_launcher->__eventEmit('_downloadCallback', $_meta);
        };

        try
        {
            //спустя три часа, и случайно добравшись до httpclient врдуг оказалось что 
            //fs::copy умеет кушать closure для callback
            $this->_resolveDir($dir);
            fs::copy($inputStream, ($dir . '/' . $name), $closureCallback, $bufferSize);
        }
        catch(IOException $exception)
        {
            if($retry) $this->_download($link, $dir, $name, false, $target);
        }        
    }

    private function _checkHash($file, $hash) : bool
    {

        $calculated_hash = new File($file)->hash('SHA-1');

        $_meta = array(
            'file' => $file,
            'hash' => $hash,
            'calculated_hash' => $calculated_hash
        );
        $this->_launcher->__eventEmit('_checkHash', $_meta);

        return ($calculated_hash == $hash);
    }
}