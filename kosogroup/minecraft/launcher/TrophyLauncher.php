<?php

namespace kosogroup\minecraft\launcher;

//devenext uses
use php\lang\Process;
use php\lang\Thread;

use kosogroup\minecraft\launcher\TrophyParser;
use kosogroup\minecraft\launcher\TrophyUtils;

class TrophyLauncher 
{
    private $_launchOptions = [];

    private $_parser;
    private $_utils;

    public function getLaunchOptions()
    {
       return $this->_launchOptions;
    } 

    public function launch($launchOptions)
    {
        $this->_launchOptions = $launchOptions;
        

        //$this->launchOptions[''];


        $this->_parser = new TrophyParser($this);
        $this->_utils = new TrophyUtils($this, $this->_parser);

        
        $minecraftJSON = $this->_parser->getMinecraftJSON();
        $updateResult = $this->_utils->downloadUpdate($minecraftJSON);
        //var_dump($updateResult);
        //var_dump($minecraftJSON);



        $launchArguments = $this->_parser->getLaunchArguments($minecraftJSON, $updateResult);
        $launchArguments = array_merge($launchArguments['jvm'], $launchArguments['game']);       
        var_dump(implode(' ', $launchArguments));
        
        return $this->runMinecraft($launchArguments);
    }

    public function runMinecraft($launchArguments) : Process
    {
        $process = new Process(array_merge(['java'], $launchArguments));

        $process = $process->start();

        $thread = new Thread(function() use ($process)
        {
            $process->getInput()->eachLine(function($line){
                uiLater(function() use ($line) {
                    var_dump($line);
                });
            });

            $process->getError()->eachLine(function($line){
                uiLater(function() use ($line) {
                    var_dump($line);
                });
            });
        });
        $thread->start();
        
        //$process->getOutput();
        //$process->getError();

        return $process;
    }
}