<?php

/* require_once 'includes/config.php'; */

class MySqlInterface
{
    private $connection;
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
        $this->connection = MySqlConnection::getInstance();
    }

    public function storeTags($tags)
    {
        $args = [
            'id' => $this->id,
            'tags' => json_encode($tags)
        ];
        $query = 'UPDATE users SET tags=:tags WHERE id=:id';

        return $this->connection->queryUsers($query, $args);
    }
}
