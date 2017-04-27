<?php

/*********************************************************
 * Interface to connect to database using PDO extension
 ********************************************************/

class MySqlConnection
{
    private static $instance = null;
    private $handle;

    public static function getInstance($configFile = null)
    {
        if (self::$instance === null) {
            self::$instance = new static($configFile);
        }

        return self::$instance;
    }

    protected function __construct($configFile)
    {
        if (!is_file($configFile))
            trigger_error("Could not find {$configFile}", E_USER_ERROR);

        $contents = file_get_contents($configFile);
        if ($contents === false)
            trigger_error("Could not read {$configFile}", E_USER_ERROR);

        $config = json_decode($contents, true)['database'];
        if (is_null($config))
            trigger_error("Could not decode {$configFile}", E_USER_ERROR);

        foreach (["servername", "name", "password", "username"] as $key)
            if (!isset($config[$key]))
                trigger_error("Missing value for database.{$key}", E_USER_ERROR);

        $this->handle = new PDO(
            "mysql:host=" . $config['servername'] . ";dbname=" . $config['name'],
            $config['username'],
            $config['password'],
            array(PDO::MYSQL_ATTR_FOUND_ROWS => true)
        );
        $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    /* separates database columns into types for type sanitising and filtering.
     * queries database using statement object and returns result set or
     * number of rows affected */
    public function queryUsers($sql, $args)
    {
        $statement = $this->handle->prepare($sql);
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
            'taskNotebookGuid' => null,
            'taskNotebookName' => null,
            'projectStack' => null,
            'tags' => null
        ];
        $userArgTypes = [
            'integers' =>  [
                'id',
                'tokenExpiration'
            ],
            'userStrings' => [
                'username',
                'firstname',
                'lastname'
            ],
            'hash' => ['hash'],
            'email' => ['email'],
            'evernoteStrings' => [
                'token',
                ':ENUserId',
                'taskNotebookGuid',
                'taskNotebookName',
                'projectStack'
            ],
            'evernoteUrl' => ['webApiUrlPrefix'],
            'tags' => ['tags']
        ];
        $stetement = $this->bindStatementArgumentsByTypes(
            $args,
            $userArgTypes,
            $statement
        );

        return $this->querySql($statement);
    }

    /* separates database columns into types for type sanitising and filtering.
     * queries database using statement object and returns result set or
     * number of rows affected */
    public function queryNotes($sql, $args)
    {
        $statement = $this->handle->prepare($sql);
        $args += [
            'userid' => null,
            'guid' => null,
            'title' => null,
            'tags' => null,
            'updated' => null,
            'notebookguid' => null
        ];
        $noteArgTypes = [
            'integers' =>  [
                'userId',
                'updated'
            ],
            'userStrings' => ['title'],
            'evernoteStrings' => [
                'guid',
                'notebookGuid'
            ],
            'tags' => ['tags'],
        ];
        $statement = $this->bindStatementArgumentsByTypes(
            $args,
            $noteArgTypes,
            $statement
        );

        return $this->querySql($statement);
    }

    public function queryNoteContent($sql, $args)
    {
        $statement = $this->handle->prepare($sql);
        $args += [
            'guid' => null,
            'updated' => null,
            'content' => null
        ];
        $noteArgTypes = [
            'integers' =>  ['updated'],
            'ENML' => ['content'],
            'evernoteStrings' => ['guid']
        ];
        $statement = $this->bindStatementArgumentsByTypes(
            $args,
            $noteArgTypes,
            $statement
        );
        $statement->debugDumpParams();

        return $this->querySql($statement);
    }

    /* bind argument to parameter in statement based on types */
    private function bindStatementArgumentsByTypes($args, $types, $statement)
    {
        foreach ($types as $type => $typeIdentifiers) {
            switch ($type) {
            case 'integers':
                $this->bindIntegers($args, $typeIdentifiers, $statement);
                break;
            case 'ENML':
                $this->bindENML($args, $typeIdentifiers, $statement);
                break;
            case 'userStrings':
                $this->bindUserStrings($args, $typeIdentifiers, $statement);
                break;
            case 'hash':
                $this->bindHash($args, $typeIdentifiers, $statement);
                break;
            case 'email':
                $this->bindEmail($args, $typeIdentifiers, $statement);
                break;
            case 'evernoteStrings':
                $this->bindEvernoteStrings($args, $typeIdentifiers, $statement);
                break;
            case 'evernoteUrl':
                $this->bindEvernoteUrl($args, $typeIdentifiers, $statement);
                break;
            case 'tags':
                $this->bindTags($args, $typeIdentifiers, $statement);
                break;
            };
        }

        return $statement;
    }

    /* attempt to execute the statement, returning appropriate result */
    private function querySql($statement)
    {
        $statement->execute();

        if ($statement->columnCount() > 0) {
            // return result set's rows for SELECT query
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // return number of rows affected for DELETE, INSERT, UPDATE queries
            return $statement->rowCount();
        }
    }

    private function bindIntegers($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_NUMBER_INT);
                $statement->bindValue(':' . $key, $var, PDO::PARAM_INT);
            }
        }
    }

    private function bindENML($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = $args[$key];
                $statement->bindValue(':' . $key, $var, PDO::PARAM_STR);
            }
        }
    }

    private function bindUserStrings($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_STRING);
                $statement->bindValue(':' . $key, $var, PDO::PARAM_STR);
            }
        }
    }

    private function bindHash($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $statement->bindValue(':' . $key, $args[$key]);
            }
        }
    }

    private function bindEmail($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_EMAIL);
                $statement->bindValue(':' . $key, $args[$key], PDO::PARAM_STR);
            }
        }
    }

    private function bindEvernoteStrings($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_STRING);
                $statement->bindValue(':' . $key, $args[$key], PDO::PARAM_STR);
            }
        }
    }

    private function bindEvernoteUrl ($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $var = filter_var($args[$key], FILTER_SANITIZE_URL);
                $statement->bindValue(':' . $key, $var);
            }
        }
    }

    private function bindTags($args, $keys, &$statement)
    {
        foreach ($keys as $key) {
            if (isset($args[$key])) {
                $statement->bindValue(':' . $key, $args[$key]);
            }
        }
    }
}
