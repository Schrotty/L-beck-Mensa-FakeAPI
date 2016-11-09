<?php
    include_once("core/Parser.class.php");

    $oParser = new Parser("http://www.studentenwerk.sh/de/essen/standorte/luebeck/mensa-luebeck/speiseplan.html");
    echo $oParser->LoadMealData();