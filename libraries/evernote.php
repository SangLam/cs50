<?php
/*hold the class interface for interacting with evernote*/

class Evernote {

	private static $token;
	private static $sandbox;
	private static $china;
	private static $client;

	public static function init($token, $sandbox, $china = false)
	{
		self::$token = $token;
		self::$sandbox = $sandbox;
		self::$china = $china;
	}

	private static function getClient() {
		if (!isset(self::$client))
			self::$client = new \Evernote\Client(self::$token, self::$sandbox, null, null, self::$china);
		return self::$client;
	}

	private static function getTaskNotebookGuid()
	{
		return 'b7fc2f7e-cd1d-46b1-8755-5062af555b71';
	}

	private static function getTaskNotes($filter)
	{
		$notesMetadataResultSpec = new \EDAM\NoteStore\NotesMetadataResultSpec([
			'includeTitle' => true,
			'includeUpdated' => true,
			'includeTagGuids' => true
		]);
		$offset = 0;
		$maxResult = 250;
		try
		{
			return self::getClient()->getUserNoteStore()->findNotesMetadata($filter, $offset, $maxResult, $notesMetadataResultSpec);
		}
		catch(Exception $e)
		{
			echo 'Message: ' . $e->getMessage();
			exit;
		}
	}

	public static function getTitle($notes)
	{
		$titles = array();
		for ($i = 0; $i < $notes->totalNotes; $i++) {
			array_push($titles, $notes->notes[$i]->title);
		}
		return $titles;
	}

	public static function getAllTaskNotes()
	{
		$filter = new \EDAM\NoteStore\NoteFilter(['notebookGuid' => self::getTaskNotebookGuid()]);

		$notes = self::getTaskNotes($filter);

		return $notes;
	}

	public static function getTaskNotesWithTerm($term)
	{
		$$filter = new \EDAM\NoteStore\NoteFilter(['notebookGuid' => self::getTaskNotebookGuid(), 'words' => $term]);

		$notes = self::getTaskNotes($filter);

		return $notes;
	}

	public static function getTaskNotesWithTag($tags)
	{
		$filter = new \EDAM\NoteStore\NoteFilter(['notebookGuid' => self::getTaskNotebookGuid(), 'tagGuids' => $tags]);
		print_r($filter);flush();

		$notes = self::getTaskNotes($filter);

		return $notes;
	}

	private static function hasMedia($content)
	{
	}

	/*********************************************************
	 *
	 * Helper functions for developemnt
	 *
	 ********************************************************/

	public static function getTagGuid()
	{
		$notes = self::getAllTaskNotes();
		$tags = array();
		foreach ($notes->notes as $note)
		{
			if(isset($note->tagGuids))
			{
				foreach($note->tagGuids as $id)
				{
					array_push($tags, $id);
				}
			}
		}
		return $tags;
	}

}
?>
