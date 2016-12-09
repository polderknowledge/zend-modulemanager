<?php
/**
 * Polder Knowledge / ModuleManager (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/modulemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Polder Knowledge (https://polderknowledge.com)
 * @license https://github.com/polderknowledge/modulemanager/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\ModuleManagerTest;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use PolderKnowledge\ModuleManager\ClientsConfigListener;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManagerInterface;

class ClientsConfigListenerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var vfsStream
     */
    private $stream;

    /**
     * @var ClientsConfigListener
     */
    private $clientConfigListener;

    /**
     * @var ModuleEvent
     */
    private $moduleEvent;

    protected function setUp()
    {
        parent::setUp();

        $this->stream = vfsStream::setup('tmp');

        $this->clientConfigListener = new ClientsConfigListener($this->stream->url());

        $configListener = $this->createMock(ConfigListener::class, ['addConfigStaticPath'], [], '', true);

        $moduleManager = $this->getMockForAbstractClass(ModuleManagerInterface::class, ['getModules', 'setModules']);
        $moduleManager->expects($this->any())->method('getModules')->willReturn([]);

        $this->moduleEvent = $this->createMock(ModuleEvent::class, ['getParam', 'getConfigListener', 'getTarget']);
        $this->moduleEvent->expects($this->any())->method('getParam')->with('client')->willReturn('localhost');
        $this->moduleEvent->expects($this->any())->method('getConfigListener')->willReturn($configListener);
        $this->moduleEvent->expects($this->any())->method('getTarget')->willReturn($moduleManager);
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::__construct
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::setClientsConfigBasePath
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Directory (/non/existing) does not exists
     */
    public function testThrowExecptionIfBasePathNotExists()
    {
        // Arrange
        $path = '/non/existing';

        // Act
        new ClientsConfigListener($path);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::__construct
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::setClientsConfigBasePath
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Directory (array) does not exists
     */
    public function testThrowExecptionIfBasePathIsNotAString()
    {
        // Arrange
        $path = [];

        // Act
        new ClientsConfigListener($path);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::attach
     */
    public function testEventManagerIsAttached()
    {
        // Arrange
        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects($this->once())->method('attach');

        // Act
        $this->clientConfigListener->attach($eventManager);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::onLoadModules
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientConfig
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientsConfigBasePath
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientModules
     * @expectedException RuntimeException
     * @expectedExceptionMessage No client domain parameter
     */
    public function testOnModulesLoadThrowExecptionIfClientDomainParamIsMissing()
    {
        // Arrange
        // ...

        // Act
        $this->clientConfigListener->onLoadModules($this->createMock(ModuleEvent::class));

        // Assert
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::onLoadModules
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientConfig
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientsConfigBasePath
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientModules
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown client
     */
    public function testOnModulesLoadThrowsExceptionIfNoConfigFileExistsForClient()
    {
        // Arrange
        // ...

        // Act
        $this->clientConfigListener->onLoadModules($this->moduleEvent);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::onLoadModules
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientConfig
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientsConfigBasePath
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientModules
     */
    public function testOnModulesLoadAddsStaticPathIfConfigFileExistsForClient()
    {
        // Arrange
        $configFile = vfsStream::newFile('localhost.config.php')->at($this->stream);
        $configFile->setContent('<?php return array();?>');

        $configListener = $this->moduleEvent->getConfigListener();
        $configListener->expects($this->once())->method('addConfigStaticPath')->with($configFile->url());

        // Act
        $this->clientConfigListener->onLoadModules($this->moduleEvent);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::onLoadModules
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientConfig
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientsConfigBasePath
     * @covers PolderKnowledge\ModuleManager\ClientsConfigListener::getClientModules
     */
    public function testOnModulesLoadUpdatesModulesIfModuleFileExistsForClient()
    {
        // Arrange
        $configFile = vfsStream::newFile('localhost.config.php')->at($this->stream);
        $configFile->setContent('<?php return array();?>');

        $moduleFile = vfsStream::newFile('localhost.modules.php')->at($this->stream);
        $moduleFile->setContent("<?php return array('dummyModule');?>");

        $moduleManagerMock = $this->moduleEvent->getTarget();
        $moduleManagerMock->expects($this->once())->method('getModules');
        $moduleManagerMock->expects($this->once())->method('setModules')->with(array('dummyModule'));

        // Act
        $this->clientConfigListener->onLoadModules($this->moduleEvent);

        // Assert
        // ...
    }
}
