<?php

/*********************************************************
 * Interface to connect to database using PDO extension
 ********************************************************/

class MySql
{
    private static $servername;
    private static $name;
    private static $username;
    private static $password;
    private static $handle;

    /* stores credential form config files into class variables */
    public static function init($configFile)
    {
        if (!is_file($configFile))
            trigger_error("Could not find {$configFile}", E_USER_ERROR);

        $contents = file_get_contents($configFile);
        if ($contents === false)
            trigger_error("Could not read {$configFile}", E_USER_ERROR);

        $config = json_decode($contents, true);
        if (is_null($config))
            trigger_error("Could not decode {$configFile}", E_USER_ERROR);

        foreach (["servername", "name", "password", "username"] as $key)
            if (!isset($config["database"][$key]))
                trigger_error("Missing value for database.{$key}", E_USER_ERROR);

        self::$servername = $config['database']['servername'];
        self::$name = $config['database']['name'];
        self::$username = $config['database']['username'];
        self::$password = $config['database']['password'];
    }

    /* creates database handle if not stored locally, and return it */
    private static function getHandle()
    {
        if (!isset(self::$handle)) {
            try {
                self::$handle = new PDO(
                    "mysql:host=" . self::$servername . ";dbname=" . self::$name,
                    self::$username,
                    self::$password
                );

                self::$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
        }
        return self::$handle;
    }

    /* separates database columns into types for type sanitising and filtering.
     * queries database using statement object and returns result set or
     * number of rows affected */
    public static Function queryUsers($sql, $args)
    {
        $args += [
            'id' => null,
            'username' => null,
            'hash' => null,
            'firstname' => null,
            'lastname' => null,
            'email' => null,
            'token' => null,
            'tokenExpiration' => null,
            'ENUserId' => null,
            'webApiUrlPrefix' => null,
            'taskFolderGuid' => null,
            'taskFolderName' => null,
            'projectStack' => null,
            'tags' => null
        ];

        $userArgTypes = [
            'integers' =>  ['id', 'tokenExpiration'],
            'userStrings' => ['username', 'firstname', 'lastname'],
            'hash' => ['hash'],
            'email' => ['email'],
            'evernoteStrings' => ['token', ':ENUserId', 'taskFolderGuid', 'taskFolderName', 'projectStack'],
            'evernoteUrl' => ['webApiUrlPrefix'],
            'tags' => ['tags']
        ];

        $statement = self::gethandle()->prepare($sql);
        $stetement = self::bindStatementArgumentsByTypes($args, $userArgTypes, $statement);

        return self::querySql($statement);
    }

    /* separates database columns into types for type sanitising and filtering.
     * queries database using statement object and returns result set or
     * number of rows affected */
    public static function queryNotes($sql, $args)
    {
        $args += [
            'userId' => NULL,
            'guid' => NULL,
            'title' => NULL,
            'tags' => NULL,
            'updated' => NULL,
            'notebookGuid' => NULL
        ];

        $noteArgTypes = [
            'integers' =>  ['userId', 'updated'],
            'userStrings' => ['title'],
            'evernoteStrings' => ['guid', 'notebookGuid'],
            'tags' => ['tags'],
        ];

        $statement = self::gethandle()->prepare($sql);
        $statement = self::bindStatementArgumentsByTypes($args, $noteArgTypes, $statement);

        return self::querySql($statement);
    }

    public static function queryNoteContent($sql, $args)
    {
        $args += [
            'guid' => NULL,
            'updated' => NULL,
            'content' => NULL
        ];

        $noteArgTypes = [
            'integers' =>  ['updated'],
            'ENML' => ['content'],
            'evernoteStrings' => ['guid']
        ];

        $statement = self::gethandle()->prepare($sql);
        $statement = self::bindStatementArgumentsByTypes($args, $noteArgTypes, $statement);

        return self::querySql($statement);
    }

    /* bind argument to parameter in statement based on types */
    private static function bindStatementArgumentsByTypes($args, $types, $statement)
    {
        foreach (array_keys($types) as $type) {
            switch ($type) {
            case 'integers':
                self::bindIntegers($args, $types[$type], $statement);
                break;
            case 'ENML':
                self::bindENML($args, $types[$type], $statement);
                break;
            case 'userStrings':
                self::bindUserStrings($args, $types[$type], $statement);
                break;
            case 'hash':
                self::bindHash($args, $types[$type], $statement);
                break;
            case 'email':
                self::bindEmail($args, $types[$type], $statement);
                break;
            case 'evernoteStrings':
                self::bindEvernoteStrings($args, $types[$type], $statement);
                break;
            case 'evernoteUrl':
                self::bindEvernoteUrl($args, $types[$type], $statement);
                break;
            case 'tags':
                self::bindTags($args, $types[$type], $statement);
                break;
            };
        }
        return $statement;
    }

    /* attempt to execute the statement, returning appropriate result */
    private static function querySql($statement)
    {
        /* print $statement->debugDumpParams(); */
        try {
            $statement->execute();
        }
        catch (PDOException $e) {
            var_dump( $e->getMessage());
        }

        if ($statement->columnCount() > 0)
        {
            // return result set's rows
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        // if query was DELETE, INSERT, or UPDATE
        else
        {
            // return number of rows affected
            return $statement->rowCount();
        }
    }

    private static function bindIntegers($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_NUMBER_INT);
                $statement->bindValue(':' . $key, $var, PDO::PARAM_INT);
            }
        }
    }

    private static function bindENML($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = $args[$key];
                $statement->bindValue(':' . $key, $var, PDO::PARAM_STR);
            }
        }

    }

    private static function bindUserStrings($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_STRING);
                $statement->bindValue(':' . $key, $var, PDO::PARAM_STR);
            }
        }

    }

    private static function bindHash($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $statement->bindValue(':' . $key, $args[$key]);
            }
        }
    }

    private static function bindEmail($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_EMAIL);
                $statement->bindValue(':' . $key, $args[$key], PDO::PARAM_STR);
            }
        }
    }

    private static function bindEvernoteStrings($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_STRING);
                $statement->bindValue(':' . $key, $args[$key], PDO::PARAM_STR);
            }
        }
    }

    private static function bindEvernoteUrl ($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_URL);
                $statement->bindValue(':' . $key, $var);
            }
        }
    }

    private static function bindTags($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $statement->bindValue(':' . $key, $args[$key]);
            }
        }
    }
}
