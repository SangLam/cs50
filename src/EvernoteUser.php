<?php

require_once 'vendor/autoload.php';
use EDAM\NoteStore\NoteFilter;

class EvernoteUser
{
    private $evernote;
    private $mySql;
    private $taskNotebookGuid;

    public function __construct(EvernoteInterface $evernote, MySqlInterface $mySql)
    {
        $this->evernote = $evernote;
        $this->mySql = $mySql;
    }

    public function getTaskNotebookGuid()
    {
        if (!isset($this->taskNotebookGuid)) {
            $this->taskNotebookGuid = $this->mySql->retrieveTaskNotebookGuid();
        }

        return $this->taskNotebookGuid;
    }

    public function getTitles($notes)
    {
        $titles = array();
        foreach ($notes as $note) {
            $titles[$note['guid']] = $note['title'];
        }

        return $titles;
    }

    public function getAllTaskNotes()
    {
        $values = ['notebookGuid' => $this->getTaskNotebookGuid()];
        $filter = new NoteFilter($values);
        $notes = $this->evernote->retrieveNotes($filter);

        return $notes;
    }

    public function getTaskNotesWithTerm($term)
    {
        $values = [
            'notebookGuid' => $this->getTaskNotebookGuid(),
            'words' => $term
        ];
        $filter = new \EDAM\NoteStore\NoteFilter($values);
        $notes = $this->evernote->retrieveNotes($filter);

        return $notes;
    }

    public function getTaskNotesWithTags($tags)
    {
        $values = [
            'notebookGuid' => $this->getTaskNotebookGuid(),
            'tagGuids' => $tags
        ];
        $filter = new \EDAM\NoteStore\NoteFilter($values);
        $notes = $this->evernote->retrieveNotes($filter);

        return $notes;
    }

    public function getTaskNoteContent($guid)
    {
        $result = $this->mySql->contentStatus($guid);

        if (!$result || $result[0]['updated']) {
            $content = $this->evernote->retrieveNoteContent($guid);
        } else {
            $content = $this->mySql->retrieveNoteContent($guid);
        }

        return $content;
    }
}
