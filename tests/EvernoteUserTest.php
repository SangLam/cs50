<?php

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class EvernoteUserTest extends TestCase
{
    private $mockInterface;
    private $mockUser;
    private $mockMySqlInterface;
    private $mockNotes = [
            [
                'guid' => 'guid1',
                'title' => 'title1',
                'updated' => 1.0,
                'notebookGuid' => 'nbguid1',
                'tags' => ['tagGuid1' => 'tag1']
            ],[
                'guid' => 'guid2',
                'title' => 'title2',
                'updated' => 2.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ['tagGuid2' => 'tag2']
            ], [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

    public function SetUp()
    {
        $this->mockInterface = $this->createMock(EvernoteInterface::class);
        $this->mockMySqlInterface = $this->createMock(MySqlInterface::class);
        $this->mockUser = new EvernoteUser(
            $this->mockInterface,
            $this->mockMySqlInterface
        );
    }

    public function testGetTasknotebookGuid()
    {
        $expected = 'taskNotebookGuid';

        $this->mockMySqlInterface->expects($this->once())
            ->method('retrieveTaskNotebookGuid')
            ->willReturn($expected);

        $results = $this->mockUser->getTaskNotebookGuid();
        $this->assertEquals(
            $expected,
            $results,
            'Did not return tasknotebookguid'
        );
        $this->assertAttributeEquals(
            $expected,
            'taskNotebookGuid',
            $this->mockUser
        );
    }

    public function testGetTitles()
    {
        $expected = [
            'guid1' => 'title1',
            'guid2' => 'title2',
            'guid3' => 'title3'
        ];

        $results = $this->mockUser->getTitles($this->mockNotes);
        $this->assertEquals(
            $expected,
            $results,
            'Failed to cerrectly get titles.'
        );
    }

    public function testGetAllTaskNotes()
    {
        $this->mockInterface->expects($this->once())
            ->method('retrieveNotes')
            ->with($this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class))
            ->willReturn($this->mockNotes);

        $results = $this->mockUser->getAllTaskNotes();
    }

    public function testGetTaskNotesWithTerm()
    {
        $expected = [$this->mockNotes[0]];

        $this->mockInterface->expects($this->once())
            ->method('retrieveNotes')
            ->with($this->contains('testnote1'))
            ->will($this->returnValue([$this->mockNotes[0]]));

        $results = $this->mockUser->getTaskNotesWithTerm('testnote1');
        $this->assertEquals($expected, $results);
    }

    public function testGetTaskNotesWithtags()
    {
        $expected = [$this->mockNotes[0], $this->mockNotes[1]];
        $mockTags = ['tagGuid1', 'tagGuid2'];

        $this->mockInterface->expects($this->once())
            ->method('retrieveNotes')
            ->with($this->contains($mockTags))
            ->will($this->returnValue($expected));

        $results = $this->mockUser->getTaskNotesWithTags($mockTags);
        $this->assertEquals($expected, $results);
    }

    public function testGetTaskNoteContentWhichNeedsUpdating()
    {
        $mockGuid = 'guid1';
        $mockContent = 'content';

        $this->mockMySqlInterface->expects($this->exactly(2))
            ->method('contentStatus')
            ->with($mockGuid)
            ->will($this->onConsecutiveCalls(null, [['updated' =>1]]));
        $this->mockInterface->expects($this->exactly(2))
            ->method('retrieveNoteContent')
            ->with($this->equalto($mockGuid))
            ->willReturn($mockContent);

        $result = $this->mockUser->getTaskNoteContent($mockGuid);
        $this->assertEquals($mockContent, $result);
        $result = $this->mockUser->getTaskNoteContent($mockGuid);
        $this->assertEquals($mockContent, $result);
    }

    public function testGetTaskNoteContentWhichIsUpdated()
    {
        $mockGuid = 'guid1';
        $mockContent = 'content';
        
        $this->mockMySqlInterface->expects($this->once())
            ->method('contentStatus')
            ->with($mockGuid)
            ->willReturn([['updated' => 0]]);
        $this->mockMySqlInterface->expects($this->once())
            ->method('retrieveNoteContent')
            ->with($mockGuid)
            ->willReturn($mockContent);

        $result = $this->mockUser->getTaskNoteContent($mockGuid);
        $this->assertEquals($mockContent, $result);
    }
}
