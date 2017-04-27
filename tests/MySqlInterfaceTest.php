<?php

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class MySqlInterfaceTest extends TestCase
{
    private $mockDBConnection;
    private $mockInterface;
    private $mockNotes = [
            [
                'guid' => 'guid1',
                'title' => 'title1',
                'updated' => 1.0,
                'notebookGuid' => 'nbguid1',
                'tags' => ['tagGuid1' => 'tag1']
            ],
            [
                'guid' => 'guid2',
                'title' => 'title2',
                'updated' => 2.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ['tagGuid2' => 'tag2']
            ],
            [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

    protected function setUp()
    {
        $this->mockDBConnection = $this->createMock(MySqlConnection::class);
        $ref = new \ReflectionProperty(MySqlConnection::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue($this->mockDBConnection);

        $this->mockInterface = new MySqlInterface(0);
    }

    public function testIsUsernameAvailableReturnsAvailableIfQueryReturnsEmpty()
    {
        $mockUsername = 'testUsername';
        $expected = 'available';

        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('SELECT'),
                $this->arrayHasKey('username')
            )
            ->willReturn([]);

        $result = $this->mockInterface->isUsernameAvailable($mockUsername);
        $this->assertEquals($expected, $result);
    }

    public function testIsUsernameAvailableReturnsUnavailableIfQueryReturnsNonEmpty()
    {
        $mockUsername = 'testUsername';
        $expected = 'unavailable';

        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('SELECT'),
                $this->arrayHasKey('username')
            )
            ->willReturn([['id' => 22]]);

        $result = $this->mockInterface->isUsernameAvailable($mockUsername);
        $this->assertEquals($expected, $result);
    }

    public function testMySqlInterfaceConstructsWithMockDBConnection()
    {
        $this->assertAttributeEquals(
            $this->mockDBConnection,
            'connection',
            $this->mockInterface
        );
    }

    public function testCreateUserFailsWhenIdNotZero()
    {
        $mockUsername = "testUsername";
        $mockPasswordHash = "testPassword";
        $mockFirstname = "testfirstname";
        $mockSurname = "testsurname";
        $mockEmail = "testemail";

        $this->expectException(Exception::class);

        $this->mockInterface->id = 1;

        $this->mockInterface->createUser($mockUsername, $mockPasswordHash, $mockFirstname, $mockSurname, $mockEmail);
    }

    public function testCreateUser()
    {
        $mockUsername = "testUsername";
        $mockPasswordHash = "testPassword";
        $mockFirstname = "testfirstname";
        $mockSurname = "testsurname";
        $mockEmail = "testemail";

        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('INSERT'),
                $this->arrayHasKey('username')
            )
            ->willReturn(1);
        $result = $this->mockInterface->createUser($mockUsername, $mockPasswordHash, $mockFirstname, $mockSurname, $mockEmail);
        $this->assertEquals($result, 1);
    }

    public function testGetIdOfUsername()
    {
        $mockUsername = 'testUsername';
        $mockId = $expected = 12;
        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('SELECT'),
                $this->arrayHasKey('username')
            ) 
            ->willReturn([['id' => $mockId]]);
        $result = $this->mockInterface->getIdOfUsername($mockUsername);
        $this->assertEquals($result, $expected, 'Expected Id was not return for given username');
        
    }

    public function testStoreTags()
    {
        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('UPDATE'),
                $this->arrayHasKey('tags')
            );
        $this->mockInterface->storeTags(0, array());
    }

    public function testStoreNote()
    {
        $mockNote = $this->mockNotes[0];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('INSERT'),
                $this->contains('guid1')
            )
            ->willReturn(1);

        $this->mockInterface->storeNote($mockNote);
    }

    public function testUpdateNote()
    {
        $mockNote = $this->mockNotes[0];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('UPDATE'),
                $this->contains('guid1')
            )
            ->willReturn(1);

        $this->mockInterface->updateNote($mockNote);
    }

    public function testNoteStatusWhenNoteIsNotInDatabase()
    {
        $mockNotes = [
            [
                'guid' => 'guid1',
                'title' => 'new note',
                'updated' => 1.0,
                'notebookGuid' => 'nbguid1',
                'tags' => ['tagGuid1' => 'tag1']
            ],
        ];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains('guid1')
            )
            ->willReturn(null);

        $result = $this->mockInterface->noteStatus($mockNotes[0]);
        $this->assertEquals(
            'new',
            $result,
            'new note not identified'
        );
    }

    public function testNoteStatusWhenContentIsUpdated()
    {
        $mockNotes = [
            [
                'guid' => 'guid2',
                'title' => 'updated content',
                'updated' => 2.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ['tagGuid1' =>'tag1']
            ]
        ];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains('guid2')
            )
            ->willReturn(
                [[
                    'updated' => 0,
                    'tags' => ['tagGuid1' =>'tag1']
                ]]
            );

        $result = $this->mockInterface->noteStatus($mockNotes[0]);
        $this->assertEquals(
            'updated',
            $result,
            'updated notecontent not identified'
        );
    }

    public function testNoteStatusWhenTagIsUpdated()
    {
        $mockNotes = [
            [
                'guid' => 'guid3',
                'title' => 'updated tag',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid3',
                'tags' => ['tagGuid3' => 'tag3']
            ]
        ];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains('guid3')
            )
            ->willReturn(
                [[
                    'updated' => 3.0,
                    'tags' => ''
                ]]
            );

        $result = $this->mockInterface->noteStatus($mockNotes[0]);
        $this->assertEquals(
            'updated',
            $result,
            'updated tags not identified'
        );
    }

    public function testNoteStatusWhenContentAndTagIsUpdated()
    {
        $mockNotes = [
            [
                'guid' => 'guid4',
                'title' => 'updated content and tags',
                'updated' => 4.0,
                'notebookGuid' => 'nbguid4',
                'tags' => ['tagGuid4' => 'tag4']
            ]
        ];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains('guid4')
            )
            ->willReturn(
                [[
                    'updated' => 0,
                    'tags' => ''
                ]]
            );

        $result = $this->mockInterface->noteStatus($mockNotes[0]);
        $this->assertEquals(
            'updated',
            $result,
            'updated content and tags not identified'
        );
    }

    public function testNoteStatusWhenNothingIsUpdated()
    {
        $mockNotes = [
            [
                'guid' => 'guid5',
                'title' => 'old note',
                'updated' => 5.0,
                'notebookGuid' => 'nbguid5',
                'tags' => ''
            ]
        ];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNotes')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains('guid5')
            )
            ->willReturn(
                [[
                    'updated' => 5.0,
                    'tags' => ''
                ]]
            );

        $result = $this->mockInterface->noteStatus($mockNotes[0]);
        $this->assertEquals(
            'unchanged',
            $result,
            'unchanged  note not identified'
        );
    }

    public function testMarkUpdatedContent()
    {
        $mockNote = ['guid' => 'guid1'];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNoteContent')
            ->with(
                $this->stringContains('UPDATED'),
                $this->contains('guid1')
            )
            ->willReturn(1);

        $this->mockInterface->markUpdatedContent($mockNote);
    }

    public function testStoreNoteContentStoresNewContent()
    {
        $mockContent = 'new mock content';

        $this->mockDBConnection->expects($this->exactly(2))
            ->method('queryNoteContent')
            ->withConsecutive(
                [
                    $this->stringContains('UPDATE'),
                    $this->contains($mockContent)
                ],
                [
                    $this->stringContains('INSERT INTO'),
                    $this->contains($mockContent)
                ]
            )
            ->will($this->onConsecutiveCalls(0, 1));

        $result = $this->mockInterface->storeNoteContent('guid1', $mockContent);
        $this->assertEquals(1, $result);
    }

    public function testStoreNoteContentStoresUpdatedContent()
    {
        $mockContent = 'formatted mock content';

        $this->mockDBConnection->expects($this->once())
            ->method('queryNoteContent')
            ->with(
                $this->stringContains('UPDATE'),
                $this->contains($mockContent)
            )
            ->willReturn(1);

        $result = $this->mockInterface->storeNoteContent('guid1', $mockContent);
        $this->assertEquals(1, $result);
    }

    public function testRetrieveTaskNotebookGuid()
    {
        $mockGuid = 'guid1';
        $this->mockDBConnection->expects($this->once())
            ->method('queryUsers')
            ->with(
                $this->stringContains('taskNotebookGuid'),
                $this->contains('0')
            )
            ->willReturn([['taskNotebookGuid' => $mockGuid]]);

        $results = $this->mockInterface->retrieveTaskNotebookGuid();
        $this->assertEquals($mockGuid, $results);
    }

    public function testContentStatus()
    {
        $mockGuid = 'guid1';
        $this->mockDBConnection->expects($this->once())
            ->method('queryNoteContent')
            ->with(
                $this->stringContains('SELECT'),
                $this->contains($mockGuid)
            );

        $this->mockInterface->contentStatus($mockGuid);
    }

    public function testRetieveNoteContent()
    {
        $mockGuid = 'guid1';
        $mockResult = [['content' => 'mockContent']];

        $this->mockDBConnection->expects($this->once())
            ->method('queryNoteContent')
            ->with(
                $this->stringContains('Select'),
                $this->contains($mockGuid)
            )->willReturn($mockResult);

        $result = $this->mockInterface->retrieveNoteContent($mockGuid);
        $this->assertEquals(
            $mockResult[0]['content'],
            $result,
            'Did not properly obtain content from results'
        );
    }
}
