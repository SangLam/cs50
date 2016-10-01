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
    private $tagList;

    public function __construct(\Evernote\Client $client, MySqlInterface $database)
    {
        $this->database = $database;
        $this->client = $client;
        $this->token = $client->getToken();
    }

    public function retrieveTagList()
    {
        $tagList = $this->getNoteStore()->listTags($this->token);
        $formattedTagList = $this->formatTagList($tagList);
        $this->tagList = $formattedTagList;

        $this->database->storeTags($formattedTagList);
        return $this->tagList;
    }

    public function retrieveNotes($filter)
    {
        $values = [
            'includeTitle' => true,
            'includeUpdated' => true,
            'includeTagGuids' => true,
            'includeNotebookGuid' => true
        ];
        $notesMetadataResultSpec = new \EDAM\NoteStore\NotesMetadataResultSpec($values);
        $noteMetadata = self::getNoteStore()->findNotesMetadata(
            $this->token,
            $filter,
            $offset = 0,
            $maxResult = 20,
            $notesMetadataResultSpec
        );
        $formattedNotes = $this->formatNotes($noteMetadata->notes);

        foreach ($formattedNotes as $note) {
            switch ($this->database->noteStatus($note)) {
            case 'new':
                $this->database->storeNote($note);
                $this->retrieveNoteContent($note['guid']);
                break;
            case 'updated':
                $this->database->updateNote($note);
                $this->database->markUpdatedContent($note);
                break;
            }
        };
        return $formattedNotes;
    }

    public function retrieveNoteContent($guid)
    {
        $content = $this->getNoteStore()->getNoteContent($this->token, $guid);
        $formattedContent = $this->formatNoteContent($guid, $content);

        $this->database->storeNoteContent($guid, $formattedContent);
        return $formattedContent;
    }

    /* not obtained in construction to allow for mocking */
    private function getNoteStore()
    {
        if (!isset($this->noteStore)) {
            $this->noteStore = $this->client->getUserNoteStore();
        }

        return $this->noteStore;
    }

    private function getTagList()
    {
        if (!isset($this->tagList)) {
            $this->tagList = $this->retrieveTagList();
        }

        return $this->tagList;

    }

    private function retrieveNewTag($tagGuid)
    {
        $newTag = $this->getNoteStore()->getTag($this->token, $tagGuid);
        $formattedTag = $this->formatTag($newTag);
        $this->tagList = array_merge($this->tagList, $formattedTag);

        return $formattedTag;
    }

    private function formatTag($tag)
    {
        $formattedTag[$tag->guid] = $tag->name;
        return $formattedTag;
    }

    private function formatTagList($tags)
    {
        $formattedTagList = array();
        foreach ($tags as $tag) {
            $formattedTagList = array_merge(
                $formattedTagList,
                $this->formatTag($tag)
            );
        }

        return $formattedTagList;
    }

    private function formatTagGuids($tagGuids)
    {
        $formattedTags = array();
        $tagList = $this->getTagList();
        foreach ($tagGuids as $tagGuid) {
            if (isset($tagList[$tagGuid])) {
                $formattedTags[$tagGuid] = $tagList[$tagGuid];
            } else {
                $newTag = $this->retrieveNewTag($tagGuid);
                $formattedTags = array_merge($formattedTags, $newTag);
            }
        }

        return $formattedTags;
    }

    private function formatNotes($notes)
    {
        foreach ($notes as &$note) {
            $note = (array) $note;
            /* time is accurate only to seconds but given in milliseconds */
            $note['updated'] = $note['updated'] * 0.001;
            if ($note['tagGuids']) {
                $note['tags'] = $this->formatTagGuids($note['tagGuids']);
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
        }

        return $notes;
    }

    private function formatNoteContent($guid, $content)
    {
        /* format content if hash appears in string*/
        $hashes = array();
        $pattern = '/\bhash="([0-9a-fA-F]*)" /';
        if (preg_match_all($pattern, $content, $hashes, PREG_SET_ORDER) > 0) {
            /* TODO: use webapiurlprefix saved from oauth method */
            $userInfo = $this->client->getAdvancedClient()->getUserStore()->getPublicUserInfo('batt76');
            foreach ($hashes as $hash) {
                $resource = $this->getNoteStore()->getResourceByHash(
                    $this->token,
                    $guid,
                    hex2bin($hash[1]),
                    $withData = false,
                    $withRecognition = false,
                    $withAlternateData = false
                );
                $pattern = "/<en-media\s[^>]*$hash[0][^>]*\/>/" ;
                $resGuid = $resource->guid;
                $resUrl = $userInfo->webApiUrlPrefix . 'res/' . $resGuid;
                $replacement = "<img src=$resUrl />";
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }
}
?>
