<?php

namespace kosogroup\minecraft\launcher\core;

//devenext uses
use php\lang\Process;
use php\lang\Thread;

use kosogroup\minecraft\launcher\core\TrophyParser;
use kosogroup\minecraft\launcher\core\TrophyUtils;

class TrophyLauncher //extends Thread
{
    private $_launchOptions = [];

    private $_parser;
    private $_utils;

    private $_launchThread = null;
    private $_process = null;

    public function __eventEmit($target, $meta)
    {
        if(isset($this->getLaunchOptions()['eventEmiter']))
            $this->getLaunchOptions()['eventEmiter']($target, $meta);
    }

    public function getLaunchOptions()
    {
       return $this->_launchOptions;
    } 

    public function launch($launchOptions)
    {
        $this->_launchOptions = $launchOptions;

        $this->_parser = new TrophyParser($this);
        $this->_utils = new TrophyUtils($this, $this->_parser);

        $this->_launchThread = new Thread(function()
        {
            //$this->launchOptions[''];

            $minecraftJSON = $this->_parser->getMinecraftJSON();
            $updateResult = $this->_utils->downloadUpdate($minecraftJSON);

            if($this->_launchThread->isInterrupted()) return $this->interruptEvent();
            $launchArguments = $this->_parser->getLaunchArguments($minecraftJSON, $updateResult);
            $launchArguments = array_merge($launchArguments['jvm'], $launchArguments['game']);
            
            if($this->_launchThread->isInterrupted()) return $this->interruptEvent();
            $this->_process = $this->runMinecraft($launchArguments);
        });
        $this->_launchThread->Start();

        return $this;
    }
    
    private function interruptEvent()
    {
        $this->__eventEmit('_interrupt', []);
    }

    public function Stop()
    {
        $this->_launchThread->interrupt();
        if($this->_utils) $this->_utils->downloadInterrupt();
        
        if($this->_process) $this->_process->destroy();
    }    


    

    public function runMinecraft($launchArguments) : Process
    {
        $process = new Process(array_merge([($this->getLaunchOptions()['javaPath'])], $launchArguments));

        $process = $process->start();

        $thread = new Thread(function() use ($process)
        {
            $process->getInput()
                ->eachLine(function($line) {
                    $this->__eventEmit("std::out", $line);
                });

            $process->getError()
                ->eachLine(function($line) {
                    $this->__eventEmit("err::out", $line);
                });
        });
        $thread->start();
        
        return $process;
    }
}