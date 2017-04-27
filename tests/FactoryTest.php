<?php

require_once 'vendor/autoload.php';
use PHPUnit\Framework\TestCase;

class EvernoteFactoryTest extends TestCase
{
    private $token = 'S=s1:U=9265e:E=15b96082f78:C=1543e570150:P=1cd:A=en-devtoken:V=2:H=b3713272a2446d768ff6b5764c1bea5c';

    protected function SetUp()
    {
        $this->mockDBConnection = $this->createMock(MySqlConnection::class);
        $ref = new \ReflectionProperty(MySqlConnection::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue($this->mockDBConnection);
        
    }

    public function testCreateClientReturnsCorrectInstance() 
    {
        $client = Factory::createEvernoteClient($this->token);
        $this->assertInstanceOf(
            \Evernote\Client::class,
            $client
        );
    }

    public function testCreateEvernoteInterfaceReturnsCorrectInstance()
    {
        $mockMySqlInterface = $this->createMock(MySqlInterface::class);
        $interface = Factory::createEvernoteInterface(
            $this->token,
            $mockMySqlInterface
        );

        $this->assertInstanceOf(
            EvernoteInterface::class,
            $interface
        );
        $this->assertAttributeInstanceOf(
            \Evernote\Client::class,
            'client',
            $interface
        );
        $this->assertAttributeInstanceOf(
            MySqlInterface::class,
            'database',
            $interface
        );
    }

    public function testCreateMysqlInterfaceWithIdSupplied()
    {
        $id = 2;
        $interface = Factory::createMySqlInterface($id);

        $this->assertInstanceOf(
            MySqlInterface::class, 
            $interface
        );
        $this->assertAttributeEquals(
            $id,
            'id',
            $interface
        );
    }

    public function testCreateMysqlInterfaceWithNoIdSupplied()
    {
        $interface = Factory::createMySqlInterface();

        $this->assertInstanceOf(
            MySqlInterface::class, 
            $interface
        );
        $this->assertAttributeEquals(
            0,
            'id',
            $interface
        );
    }

    public function testCreateEvernoteUser()
    {
        $EvernoteUser = Factory::createEvernoteUser($this->token, 0);

        $this->assertInstanceOf(
            EvernoteUser::class,
            $EvernoteUser
        );
        $this->assertAttributeInstanceOf(
            EvernoteInterface::class,
            'evernote',
            $EvernoteUser
        );
        $this->assertAttributeInstanceOf(
            MySqlInterface::class,
            'mySql',
            $EvernoteUser
        );
    }
}
