<?php
/**
 * Polder Knowledge / ModuleManager (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/zend-modulemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Polder Knowledge (https://polderknowledge.com)
 * @license https://github.com/polderknowledge/zend-modulemanager/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\ModuleManagerTest;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory;
use Zend\Console\Console;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\ServiceManager;

class ClientsConfigDelegatorFactoryTest extends TestCase
{
    /**
     * @var ClientsConfigDelegatorFactory
     */
    private $delegator;

    /**
     * @var callback
     */
    private $delegatorCallback;
    
    /**
     * @var ServiceManager
     */
    private $serviceLocator;

    /**
     * @var ModuleManager
     */
    private $moduleManager;
    
    public function setUp()
    {
        parent::setUp();

        // Simulate the console
        Console::overrideIsConsole(true);
        $_SERVER['argv'][] = '--client=localhost';

        $this->delegator = new ClientsConfigDelegatorFactory();
        
        $this->serviceLocator = $this->getMockForAbstractClass(ServiceManager::class);
        $this->serviceLocator->setService('ApplicationConfig', [
            'module_listener_options' => [
                'config_cache_key' => 'app_config',
                'module_map_cache_key' => 'module_map',
            ],
            'clients_config_path' => vfsStream::setup('tmp')->url()
        ]);

        $eventManagerMock = $this->getMockForAbstractClass('Zend\EventManager\EventManagerInterface');
        $moduleEventMock = $this->getMockBuilder(ModuleEvent::class)->getMock();
        
        $moduleManagerBuilder = $this->getMockBuilder(ModuleManager::class);
        $moduleManagerBuilder->disableOriginalConstructor();
        $moduleManagerBuilder->setMethods(['getEventManager', 'getEvent']);

        $this->moduleManager = $moduleManagerBuilder->getMockForAbstractClass();
        $this->moduleManager->expects($this->any())->method('getEventManager')->willReturn($eventManagerMock);
        $this->moduleManager->expects($this->any())->method('getEvent')->willReturn($moduleEventMock);

        $moduleManager = $this->moduleManager;
        $this->delegatorCallback = function () use ($moduleManager) {
            return $moduleManager;
        };
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::__invoke
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::getClientId
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not determine client domain
     */
    public function testInvokeWithoutClientOnCli()
    {
        // Arrange
        $_SERVER['argv'] = [''];

        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->never())->method('attach');

        // Act
        $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::__invoke
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::getClientId
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not determine client domain
     */
    public function testInvokeWithoutClientOnHttp()
    {
        // Arrange
        Console::overrideIsConsole(false);
        $_SERVER['HTTP_HOST'] = '';

        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->never())->method('attach');

        // Act
        $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);

        // Assert
        // ...
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::__invoke
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::getClientId
     */
    public function testInvokeWithClientOnCli()
    {
        // Arrange
        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->once())->method('attach');

        $event = $this->moduleManager->getEvent();
        $event->expects($this->once())->method('setParam')->with('client', 'localhost');

        // Act
        $result = $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);

        // Assert
        $this->assertInstanceOf(ModuleManager::class, $result);
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::__invoke
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::getClientId
     */
    public function testInvokeWithClientOnHttp()
    {
        // Arrange
        Console::overrideIsConsole(false);
        $_SERVER['HTTP_HOST'] = 'localhost';

        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->once())->method('attach');

        $event = $this->moduleManager->getEvent();
        $event->expects($this->once())->method('setParam')->with('client', 'localhost');

        // Act
        $result = $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);

        // Assert
        $this->assertInstanceOf(ModuleManager::class, $result);
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::createDelegatorWithName
     */
    public function testCreateDelegatorWithName()
    {
        // Arrange
        // ...

        // Act
        $result = $this->delegator->createDelegatorWithName(
            $this->serviceLocator,
            'modulemanager',
            'modulemanager',
            $this->delegatorCallback
        );

        // Assert
        $this->assertInstanceOf(ModuleManager::class, $result);
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::adjustApplicationConfig
     */
    public function testApplicationSettingConfigCacheKeyIsSetToClientHost()
    {
        // Arrange
        Console::overrideIsConsole(false);
        $_SERVER['HTTP_HOST'] = 'localhost123';

        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->once())->method('attach');

        $event = $this->moduleManager->getEvent();
        $event->expects($this->once())->method('setParam')->with('client', 'localhost123');

        // Act
        $result = $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);
        $config = $this->serviceLocator->get('ApplicationConfig');

        // Assert
        $this->assertInstanceOf(ModuleManager::class, $result);
        $this->assertEquals($config['module_listener_options']['config_cache_key'], 'localhost123');
    }

    /**
     * @covers PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory::adjustApplicationConfig
     */
    public function testApplicationSettingModuleMapCacheKeyIsSetToClientHost()
    {
        // Arrange
        Console::overrideIsConsole(false);
        $_SERVER['HTTP_HOST'] = 'localhost123';

        $eventManager = $this->moduleManager->getEventManager();
        $eventManager->expects($this->once())->method('attach');

        $event = $this->moduleManager->getEvent();
        $event->expects($this->once())->method('setParam')->with('client', 'localhost123');

        // Act
        $result = $this->delegator->__invoke($this->serviceLocator, 'modulemanager', $this->delegatorCallback);
        $config = $this->serviceLocator->get('ApplicationConfig');

        // Assert
        $this->assertInstanceOf(ModuleManager::class, $result);
        $this->assertEquals($config['module_listener_options']['module_map_cache_key'], 'localhost123');
    }
}
