<?php
/* require 'vendors/evernote/vendor/autoload.php'; */
require_once 'vendor/autoload.php';
/*hold the class interface for interacting with evernote*/
class EvernoteInterface
{
    private $database;
    private $client;
    private $noteStore;
    private $token;

    public function __construct(\Evernote\Client $client, MySqlInterface $database)
    {
        $this->database = $database;
        $this->client = $client;
        $this->token = $client->getToken();
        
    }

    public function retrieveTagList()
    {
        $tags = $this->getNoteStore()->listTags($this->token);
        $formattedTags = $this->formatTags($tags);
        $this->database->storeTags($formattedTags);

        return $formattedTags;
    }

    /* not obtained in construction to allow for mocking */
    private function getNoteStore()
    {
        if (!isset($this->noteStore)) {
            $this->noteStore = $this->client->getUserNoteStore();
        }

        return $this->noteStore;
    }

    private function formatTags($tags)
    {
        $formattedTags = array();
        foreach ($tags as $tag) {
            $formattedTags[$tag->guid] = $tag->name;
        }

        return $formattedTags;
    }

}
class EvernoteOld
{
    private static $token;
    private static $sandbox;
    private static $china;
    private static $client;
    private static $notestore;
    private static $tagList;

    public static function init($token, $sandbox, $china = false)
    {
        self::$token = $token;
        self::$sandbox = $sandbox;
        self::$china = $china;
    }

    public static function getAllTaskNotes()
    {
        $values = [
            'notebookGuid' => self::getTaskNotebookGuid()
        ];
        $filter = new \EDAM\NoteStore\NoteFilter($values);
        $notes = self::getEvernoteTaskNotes($filter);

        return $notes;
    }

    public static function getTaskNotesWithTerm($term)
    {
        $values = [
            'notebookGuid' => self::getTaskNotebookGuid(),
            'words' => $term
        ];
        $filter = new \EDAM\NoteStore\NoteFilter($values);
        $notes = self::getEvernoteTaskNotes($filter);

        return $notes;
    }

    public static function getTaskNotesWithTag($tags)
    {
        $values = [
            'notebookGuid' => self::getTaskNotebookGuid(),
            'tagGuids' => $tags
        ];
        $filter = new \EDAM\NoteStore\NoteFilter($values);
        $notes = self::getEvernoteTaskNotes($filter);

        return $notes;
    }

    public static function getTaskNoteContent($guid)
    {
        $args = [
            'guid' => $guid,
        ];
        $query = 'SELECT updated FROM notecontent WHERE guid=:guid';
        $result = mysql::queryNoteContent($query, $args);

        if (!$result) {
            $content = self::getNewTaskNoteContent($guid);
        } else {
            if ($result[0]['updated']) {
                $content = self::updateNewTaskNoteContent($guid);
            } else {
                $query = 'SELECT content FROM notecontent WHERE guid=:guid';
                $content = mysql::queryNoteContent($query, $args)[0]['content'];
            }
        }

        return $content;
    }

    /* returns a list of {key, value}={guid,titles} of the notes (metadata) */
    public static function getTitle($notes)
    {
        $titles = array();
        foreach ($notes as $note) {
            $titles[$note['guid']] = $note['title'];
        }

        return $titles;
    }

    private static function getClient()
    {
        if (!isset(self::$client)) {
            self::$client = new \Evernote\Client(self::$token, self::$sandbox, null, null, self::$china);
        }

        return self::$client;
    }

    private static function getNoteStore()
    {
        if (!isset(self::$notestore)) {
            self::$notestore = self::getClient()->getUserNoteStore();
        }

        return self::$notestore;
    }

    private static function getTagList()
    {
        if (!isset(self::$tagList)) {
            $args = [
                'id' => $_SESSION['id']
            ];
            $query = 'SELECT tags FROM users WHERE id=:id';
            $results = mysql::queryUsers($query, $args);
            self::$tagList = json_decode($results[0]['tags'], $asArray = true);
        }

        return self::$tagList;
    }

    private static function getEvernoteNewTag($tagGuid)
    {
        $newTag = self::getNotestore()->getTag($tagGuid);
        $newTag = [$tagGuid => $newTag->name];
        self::$tagList = array_merge(self::$tagList, $newTag);

        $args = [
            'id' => $_SESSION['id'],
            'tags' => json_encode($newTag)
        ];
        $query = 'UPDATE users SET tags=JSON_MERGE(tags, :tags) WHERE id=:id';
        mysql::queryUsers($query, $args);

        return $newTag;
    }

    /* gets new tag information from evernote, formats to working tag array,
     * appends to local and db taglist appropiately and returns formatted tag
     * as an array */
    /* TODO: take argument to choose notebook */
    private static function getTaskNotebookGuid()
    {
        return 'b7fc2f7e-cd1d-46b1-8755-5062af555b71';
    }

    /* uses evernote task filter to filter retrieved notes (metadata) */
    private static function getEvernoteTaskNotes($filter)
    {
        $values = [
            'includeTitle' => true,
            'includeUpdated' => true,
            'includeTagGuids' => true,
            'includeNotebookGuid' => true
        ];
        $notesMetadataResultSpec = new \EDAM\NoteStore\NotesMetadataResultSpec($values);
        $notes = self::getNoteStore()->findNotesMetadata(
            $filter,
            $offset = 0,
            $maxResult = 20,
            $notesMetadataResultSpec
        );

        /* frankenstein note object into array and to what's needed */
        foreach ($notes->notes as &$note) {
            $note = (array) $note;
            /* time us accurate only to seconds but given in milliseconds */
            $note['updated'] = $note['updated'] * 0.001;
            if ($note['tagGuids']) {
                $note['tags'] = self::formatTagGuidList($note['tagGuids']);
            } else {
                $note['tags'] = '';
            }
            unset(
                $note['contentLength'],
                $note['created'],
                $note['deleted'],
                $note['updateSequenceNum'],
                $note['tagGuids'],
                $note['attributes'],
                $note['largestResourceMime'],
                $note['largestResourceSize']
            );

            switch (self::status($note)) {
            case 'new':
                $args = array_merge(['userId' => $_SESSION['id']], $note);
                $query = 'INSERT INTO notes (userId, guid, title, tags, updated, notebookGuid) VALUES (:userId, :guid, :title, :tags, :updated, :notebookGuid)';
                mysql::queryNotes($query, $args);
                self::getTaskNoteContent($note['guid']);
                break;
            case 'updated':
                $query = 'UPDATE notes SET title=:title, tags=:tags, updated=:updated, notebookGuid=:notebookGuid WHERE guid=:guid';
                mysql::queryNotes($query, $note);
                self::markNewNoteContent($note);
                break;
            };
        }

        return $notes->notes;
    }

    private static function markNewNoteContent($note)
    {
        $args = [
            'guid' => $note['guid'],
            'updated' => 1
        ];
        $query = 'UPDATE notecontent SET updated=:updated WHERE guid=:guid';
        $result = mysql::queryNoteContent($query, $args);

        return $result;
    }

    private static function status($note)
    {
        $args = [
            'guid' => $note['guid']
        ];
        $query = 'SELECT updated, tags FROM notes WHERE guid=:guid';
        $results = mysql::queryNotes($query, $args);

        if (!$results) {
            return 'new';
        } else {
            // Workaround for tag updated not reflected in 'update'
            $dbTags = json_decode($results[0]['tags']);
            $noteTags = json_decode($note['tags']);
            $tagUpdated = $dbTags != $noteTags;
            $stringUpdated = $results[0]['updated'] != $note['updated'];
            if ($stringUpdated || $tagUpdated) {
                return 'updated';
            }
        }
    }

    private static function formatTagGuidList($tagGuids)
    {
        $formattedTags = array();
        $tagList = self::getTagList();
        foreach ($tagGuids as $tagGuid) {
            if (isset($tagList[$tagGuid])) {
                $formattedTags[$tagGuid] = $tagList[$tagGuid];
            } else {
                $newTag = self::getEvernoteNewTag($tagGuid);
                $formattedTags = array_merge($formattedTags, $newTag);
            }
        }

        return json_encode($formattedTags);
    }

    private static function getNewTaskNoteContent($guid)
    {
        $content = self::getEvernoteTaskNoteContent($guid);
        $args = [
            'guid' => $guid,
            'updated' => 0,
            'content' => $content
        ];
        $query = 'INSERT INTO notecontent (guid, updated, content) VALUES (:guid, :updated, :content)';
        mysql::queryNoteContent($query, $args);

        return $content;
    }

    private static function updateNewTaskNoteContent($guid)
    {
        $content = self::getEvernoteTaskNoteContent($guid);
        $args = [
            'guid' => $guid,
            'updated' => 0,
            'content' => $content
        ];
        $query = 'UPDATE notecontent SET content=:content, updated=:updated WHERE guid=:guid';
        mysql::queryNoteContent($query, $args);

        return $content;
    }

    /* TODO: format different filetypes */
    private static function getEvernoteTaskNoteContent($guid)
    {
        $content = self::getNoteStore()->getNoteContent($guid);

        /* format content if hash appears in string*/
        $hashes = array();
        $pattern = '/\bhash="([0-9a-fA-F]*)" /';
        if (preg_match_all($pattern, $content, $hashes, PREG_SET_ORDER) > 0) {
            $content = self::formatTaskNoteContent($guid, $hashes, $content);
        }

        return $content;
    }

    private static function formatTaskNoteContent($guid, $hashes, $content)
    {
        /* TODO: use webapiurlprefix saved from oauth method */
        $userInfo = self::getclient()->getAdvancedClient()->getUserStore()->getPublicUserInfo('batt76');
        foreach ($hashes as $hash) {
            $resource = self::getNoteStore()->getResourceByHash(
                $guid,
                hex2bin($hash[1]),
                $withData = false,
                $withRecognition = false,
                $withAlternateData = false
            );
            $pattern = "/<en-media\s[^>]*$hash[0][^>]*\/>/" ;
            $resGuid = $resource->guid;
            $resUrl = $userInfo->webApiUrlPrefix . 'res/' . $resGuid;
            $replacement = "<img src=$resUrl>";
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

/*********************************************************
 *
 * Methods implemented in EvernoteInterface
 *
 ********************************************************/
    public static function updateEvernoteTagList()
    {
        $formattedTags = array();
        $tags = self::getNoteStore()->listTags();
        foreach ($tags as $tag) {
            $formattedTags[$tag->guid] = $tag->name;
        }
        self::$tagList = $formattedTags;

        $args = [
            'id' => $_SESSION['id'],
            'tags' => json_encode($formattedTags)
        ];
        $query = 'UPDATE users SET tags=:tags WHERE id=:id';
        mysql::queryUsers($query, $args);

        return $formattedTags;
    }

}
?>
