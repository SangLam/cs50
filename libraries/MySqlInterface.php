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

    public function storeNote($note)
    {
        $note['tags'] = json_encode($note['tags']);
        $args = array_merge(['userId' => $this->id], $note);
        $query = 'INSERT INTO notes (userId, guid, title, tags, updated, notebookGuid) VALUES (:userId, :guid, :title, :tags, :updated, :notebookGuid)';

        return $this->connection->queryNotes($query, $args);
    }

    public function updateNote($note)
    {
        $note['tags'] = json_encode($note['tags']);
        $query = 'UPDATE notes SET title=:title, tags=:tags, updated=:updated, notebookGuid=:notebookGuid WHERE guid=:guid';

        return $this->connection->queryNotes($query, $note);
    }

    public function storeNoteContent($guid, $content)
    {
        $args = [
            'guid' => $guid,
            'updated' => 0,
            'content' => $content
        ];
        $query = 'UPDATE notecontent SET content=:content, updated=:updated WHERE guid=:guid';

        $result = $this->connection->queryNoteContent($query, $args);
        if ($result == 0) {
            $query = 'INSERT INTO notecontent (guid, updated, content) VALUES (:guid, :updated, :content)';
            return $this->connection->queryNoteContent($query, $args);
        } else {
            return $result;
        }
    }

    public function noteStatus($note)
    {
        $args = ['guid' => $note['guid']];
        $query = 'SELECT updated, tags FROM notes WHERE guid=:guid';
        $results = $this->connection->queryNotes($query, $args);

        if (!$results) {
            return 'new';
        } else {
            return $this->noteUpdated($note, $results) ? 'updated' : 'unchanged';
        }
    }

    public function markUpdatedContent($note)
    {
        $args = [
            'guid' => $note['guid'],
            'updated' => 1
        ];
        $query = 'UPDATE notecontent SET updated=:updated WHERE guid=:guid';

        return $this->connection->queryNoteContent($query, $args);
    }

    private function noteUpdated($ENNote, $DBNote)
    {
        return $this->contentUpdated($ENNote, $DBNote) || $this->tagUpdated($ENNote, $DBNote);
    }

    private function contentUpdated($ENNote, $DBNote)
    {
        return $ENNote['updated'] != $DBNote[0]['updated'];
    }

    private function tagUpdated($ENNote, $DBNote)
    {
        $DBTags = json_decode($DBNote[0]['tags']);
        $ENTags = $ENNote['tags'];

        return $ENTags != $DBTags;
    }

}
