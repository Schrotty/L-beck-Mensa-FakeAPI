<?php
/**
 * Created by PhpStorm.
 * User: ruben
 * Date: 21.03.2017
 * Time: 21:49
 */

namespace api\core;

/**
 * Class Cache
 *
 * @package api\core
 */
class Cache
{
    /**
     *
     */
    const sFile = '/storage/cache/cache.json';

    /**
     * store
     *
     * @param $sData
     */
    public static function store($sData) {
        try{
            $rHandle = fopen(getcwd() . Cache::sFile, 'w+');
            if (!$rHandle) throw new \Exception('Failed open cache.json!');

            fwrite($rHandle, $sData);
        }catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * retrieve
     *
     * @return null|string
     */
    public static function retrieve(){
        $sResult = null;

        try {
            $rHandle = fopen(getcwd() . Cache::sFile, 'r');
            if (!$rHandle) throw new \Exception('Failed open cache.json!');

            $sResult = fread($rHandle, filesize(getcwd() . Cache::sFile));
        }catch(\Exception $exception) {
            echo $exception->getMessage();
        }

        return json_decode($sResult);
    }
}