<?php

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class evernoteInterfaceTest extends TestCase
{
    private $mockDatabase;
    private $mockClient;
    private $mockNoteStore;
    private $mockInterface;

    public function setUp()
    {
        $this->mockDatabase = $this->createMock(MySqlInterface::class);
        $this->mockClient = $this->createMock(\Evernote\Client::class);
        $this->mockNoteStore = $this->createMock(\EDAM\NoteStore\NoteStoreClient::class);
        $this->mockInterface = new EvernoteInterface($this->mockClient, $this->mockDatabase);

        $this->mockClient->expects($this->any())
            ->method('getUserNoteStore')
            ->will($this->returnValue($this->mockNoteStore));
    }

    public function testConstructSetsClient()
    {
        $this->assertAttributeInstanceOf(\Evernote\Client::class, 'client', $this->mockInterface);
    }

    public function testConstructFailsWhenGivenIncorrectArgument()
    {
        $this->expectException(TypeError::class);

        new EvernoteInterface();
    }

    public function testRetrieveTagList()
    {
        $mockTags = [
            new \EDAM\Types\Tag([
                "guid" => 'abcdefg',
                'name' => 'test1'
            ]),
            new \EDAM\Types\Tag([
                'guid' => 'abcdef',
                'name' => 'test2'
            ])
        ];
        $expected = [
            'abcdefg' => 'test1',
            'abcdef' => 'test2'
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('listTags')
            ->will($this->returnValue($mockTags));
        $this->mockDatabase->expects($this->once())
            ->method('storeTags')
            ->with($this->arrayHasKey('abcdefg'));

        $actual = $this->mockInterface->retrieveTagList();

        $this->assertEquals($expected, $actual, 'tags formatted incorrectly');
    }

    public function testRetrieveNotesReturnsProperlyFormattedNoteWithoutTags()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3000,
                'tagGuids' => '',
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ])
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $expected = [
            [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockDatabase->expects($this->once())
            ->method('noteStatus')
            ->with($this->equalTo($expected[0]))
            ->willReturn('unchanged');

        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }
    
    public function testRetrieveNotesReturnsProperlyFormattedNoteWithExistingTags()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid1',
                'title' => 'title1',
                'updated' => 1000,
                'tagGuids' =>['tagGuid1'],
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid1',
                'attributes' => ''
            ]),
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $mockTags = [new \EDAM\Types\Tag([
                "guid" => 'tagGuid1',
                'name' => 'tag1'
            ])];
        $expected = [
            [
                'guid' => 'guid1',
                'title' => 'title1',
                'updated' => 1.0,
                'notebookGuid' => 'nbguid1',
                'tags' => ['tagGuid1' => 'tag1']
            ]
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockNoteStore->expects($this->once())
            ->method('listTags')
            ->will($this->returnValue($mockTags));
        $this->mockDatabase->expects($this->once())
            ->method('noteStatus')
            ->with($this->equalTo($expected[0]))
            ->willReturn('unchanged');
        
        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }

    public function testRetrieveNotesReturnProperlyFormattedNoteWithNewTag()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid2',
                'title' => 'title2',
                'updated' => 2000,
                'tagGuids' => ['newTagGuid'],
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ]),
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $mockTags = [new \EDAM\Types\Tag([
            "guid" => 'tagGuid1',
            'name' => 'tag1'
        ])];
        $mockNewTag = new \EDAM\Types\Tag([
            'guid' => 'newTagGuid',
            'name' => 'newTag'
        ]);
        $expected = [
            [
                'guid' => 'guid2',
                'title' => 'title2',
                'updated' => 2.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ['newTagGuid' => 'newTag']
            ],
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockNoteStore->expects($this->once())
            ->method('listTags')
            ->will($this->returnValue($mockTags));
        $this->mockNoteStore->expects($this->once())
            ->method('getTag')
            ->with(
                $this->anything(),
                $this->equalTo('newTagGuid')
            )
            ->will($this->returnValue($mockNewTag));
        $this->mockDatabase->expects($this->once())
            ->method('noteStatus')
            ->with($this->equalTo($expected[0]))
            ->willReturn('unchange');

        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }

    public function testRetrieveNotesStoresNewNotes()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3000,
                'tagGuids' => '',
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ])
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $expected = [
            [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockDatabase->expects($this->once())
            ->method('noteStatus')
            ->with($this->equalTo($expected[0]))
            ->willReturn('new');
        $this->mockDatabase->expects($this->once())
            ->method('storeNote')
            ->with($this->contains('guid3'));
        $this->mockNoteStore->expects($this->once())
            ->method('getNoteContent')
            ->with(
                $this->anything(),
                $this->equalTo('guid3')
            );


        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }
    
    public function testRetrieveNotesStoresUpdatedNotes()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3000,
                'tagGuids' => '',
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ])
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $expected = [
            [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockDatabase->expects($this->once())
            ->method('noteStatus')
            ->with($this->equalTo($expected[0]))
            ->willReturn('updated');
        $this->mockDatabase->expects($this->once())
            ->method('updateNote')
            ->with($this->contains('guid3'));
        $this->mockDatabase->expects($this->once())
            ->method('markUpdatedContent')
            ->with($this->equalTo($expected[0]));


        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }
    
    public function testRetrieveNotesMix()
    {
        $mockFilter = $this->createMock(EDAM\NoteStore\NoteFilter::class);
        $mockNotes = [
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid1',
                'title' => 'title1',
                'updated' => 1000,
                'tagGuids' =>['tagGuid1'],
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid1',
                'attributes' => ''
            ]),
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid2',
                'title' => 'title2',
                'updated' => 2000,
                'tagGuids' => ['newTagGuid'],
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ]),
            new \EDAm\NoteStore\NoteMetadata([
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3000,
                'tagGuids' => '',
                'contentlength' => '',
                'created' => '',
                'deleted' => '',
                'active' => '',
                'updateSequenceNum' => '',
                'notebookGuid' => 'nbguid2',
                'attributes' => ''
            ])
        ];
        $mockNoteList = new \EDAM\NoteStore\NotesMetadataList(['notes' => $mockNotes]);
        $mockTags = [new \EDAM\Types\Tag([
                "guid" => 'tagGuid1',
                'name' => 'tag1'
            ])];
        $mockNewTag = new \EDAM\Types\Tag([
                'guid' => 'newTagGuid',
                'name' => 'newTag'
            ]);
        $expected = [
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
                'tags' => ['newTagGuid' => 'newTag']
            ],
            [
                'guid' => 'guid3',
                'title' => 'title3',
                'updated' => 3.0,
                'notebookGuid' => 'nbguid2',
                'tags' => ''
            ]
        ];

        $this->mockNoteStore->expects($this->once())
            ->method('findNotesMetadata')
            ->with(
                $this->anything(),
                $this->isInstanceOf(\EDAM\NoteStore\NoteFilter::class),
                $this->isType('int'),
                $this->isType('int'),
                $this->isInstanceOf(\EDAM\NoteStore\NotesMetadataResultSpec::class)
            )
            ->will($this->returnValue($mockNoteList));
        $this->mockNoteStore->expects($this->once())
            ->method('listTags')
            ->will($this->returnValue($mockTags));
        $this->mockNoteStore->expects($this->once())
            ->method('getTag')
            ->with(
                $this->anything(),
                $this->equalTo('newTagGuid')
            )
            ->will($this->returnValue($mockNewTag));
        $this->mockDatabase->expects($this->exactly(3))
            ->method('noteStatus')
            ->withConsecutive(
                [$this->equalTo($expected[0])],
                [$this->equalTo($expected[1])],
                [$this->equalTo($expected[2])]
            )
            ->will($this->onConsecutiveCalls('new', 'updated', 'unchanged'));
        $this->mockDatabase->expects($this->once())
            ->method('storeNote')
            ->with($this->contains('guid1'));
        $this->mockNoteStore->expects($this->once())
            ->method('getNoteContent')
            ->with(
                $this->anything(),
                $this->equalTo('guid1')
            );
        $this->mockDatabase->expects($this->once())
            ->method('updateNote')
            ->with($this->contains('guid2'));
        $this->mockDatabase->expects($this->once())
            ->method('markUpdatedContent')
            ->with($this->equalTo($expected[1]));

        $actual = $this->mockInterface->retrieveNotes($mockFilter);
        $this->assertEquals($expected, $actual, 'notes are not formatted properly');
    }

    public function testRetrieveNoteContentStoresContentInDatabase()
    {
        $mockContent = 'content';
        $expected = 'content';
        $mockGuid = 'guid1';
        $this->mockNoteStore->expects($this->once())
            ->method('getNoteContent')
            ->with(
                $this->anything(),
                $this->equalTo($mockGuid)
            )
            ->willReturn($mockContent);
        $this->mockDatabase->expects($this->once())
            ->method('storeNoteContent')
            ->with(
                $this->equalTo($mockGuid),
                $this->equalTo($expected)
            );
        
        $this->assertEquals($expected, $this->mockInterface->retrieveNoteContent('guid1'));
    }

    public function testRetrieveNoteContentProperlyFormatsImage()
    {
        $mockContent = '<en-media type="image/jpeg" hash="65d8381b16dee510d43921a15acd06a6" />';
        $expected = '<img src=https://sandbox.evernote.com/shard/s1/res/9c3b0798-5895-43ef-aa15-83b5d760a583 />';
        $mockGuid = 'guid1';
        $mockAdvancedClient = $this->createMock(\Evernote\AdvancedClient::class);
        $mockUserStore = $this->createMock(\EDAM\UserStore\UserStoreClient::class);
        $mockPublicUserInfo = new EDAM\UserStore\PublicUserInfo([
            'webApiUrlPrefix' => 'https://sandbox.evernote.com/shard/s1/'
        ]);
        $mockResource = new \EDAM\Types\Resource([
            'guid' => '9c3b0798-5895-43ef-aa15-83b5d760a583'
        ]);

        $this->mockNoteStore->expects($this->once())
            ->method('getNoteContent')
            ->with(
                $this->anything(),
                $this->equalTo($mockGuid)
            )
            ->willReturn($mockContent);
        $this->mockClient->expects($this->once())
            ->method('getAdvancedClient')
            ->will($this->returnValue($mockAdvancedClient));
        $mockAdvancedClient->expects($this->once())
            ->method('getUserStore')
            ->will($this->returnValue($mockUserStore));
        $mockUserStore->expects($this->once())
            ->method('getPublicUserInfo')
            ->will($this->returnValue($mockPublicUserInfo));
        $this->mockNoteStore->expects($this->once())
            ->method('getResourceByHash')
            ->with(
                $this->anything(),
                $this->equalTo($mockGuid),
                $this->equalTo(hex2bin("65d8381b16dee510d43921a15acd06a6")),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(false)
            )
            ->will($this->returnValue($mockResource));

        $this->assertEquals($expected, $this->mockInterface->retrieveNoteContent('guid1'));

    }
}

