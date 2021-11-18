<?php

namespace kosogroup\minecraft\launcher\core;

//devenext uses
use php\lang\Process;
use php\lang\Thread;

use kosogroup\minecraft\launcher\core\TrophyParser;
use kosogroup\minecraft\launcher\core\TrophyUtils;

class TrophyVersion
{
    private static $METAURI = "https://launchermeta.mojang.com";

    public static function DeployMeta($metaURI)
    {
        static::$METAURI = $metaURI;
    }

    private static function getVersionManifest()
    {
        return json_decode(file_get_contents(static::$METAURI, true));
    }

    public static function getAll()
    {
        return static::getVersionManifest()['versions'];
    }

    public static function getByNumber($versionNumber)
    {
        foreach(static::getAll() as $version)
        if($version['id'] == $versionNumber)
        return $version;
    }
    
    public static function getLatestRelease()
    {
        return static::getByNumber(static::getVersionManifest()['latest']['release']);
    }
}