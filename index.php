<?php
use api\core\Cache;
use api\core\Parser;

include_once("core/Parser.class.php");
    include_once("core/Cache.class.php");

    try {
        //create new parser
        $oParser = new Parser("http://www.studentenwerk.sh/de/essen/standorte/luebeck/mensa-luebeck/speiseplan.html");

        //load mela data & cache it
        Cache::store(json_encode($oParser->LoadMealData()));

        //cache retrieve
        echo Cache::retrieve();
    }catch (Exception $exception){
        echo $exception->getMessage();
    }