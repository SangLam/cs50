<?php

require_once 'vendor/autoload.php';

class Factory
{
    public static function createEvernoteClient($token)
    {
        $sandbox = true;
        $advancedClient = null;
        $logger = null;
        $china = false;
        return new \Evernote\Client($token, $sandbox, $advancedClient, $logger, $china);
    }

    public static function createEvernoteInterface($token, $mySqlInterface)
    {
        return new EvernoteInterface(
            self::createEvernoteClient($token),
            $mySqlInterface
        );
    }

    public static function createEvernoteUser($token, $id)
    {
        $mySqlInterface = self::createMySqlInterface($id);
        return new EvernoteUser(
            self::createEvernoteInterface($token, $mySqlInterface),
            $mySqlInterface
        );
    }

    public static function createMySqlInterface($id = null)
    {
        if (isset($id)) {
            return new MySqlInterface($id);
        } else
        {
            return new MySqlInterface(0);
        }
    }
}
