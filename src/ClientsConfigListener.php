<?php
/**
 * Polder Knowledge / ModuleManager (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/modulemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Polder Knowledge (https://polderknowledge.com)
 * @license https://github.com/polderknowledge/modulemanager/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\ModuleManager;

use InvalidArgumentException;
use RuntimeException;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\ModuleEvent;

/**
 * This listener will modify the application config before the application
 * is bootstrapped to load client specific configuration.
 */
class ClientsConfigListener extends AbstractListenerAggregate
{
    /**
     * @var string
     */
    private $clientsConfigBasePath;

    /**
     * Initializes a new instance of ClientsConfigListener.
     *
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->setClientsConfigBasePath($basePath);
    }

    /**
     * @param EventManagerInterface $events
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ModuleEvent::EVENT_LOAD_MODULES, [$this, 'onLoadModules'], 500);
    }

    /**
     * @param ModuleEvent $event
     * @throws RuntimeException
     */
    public function onLoadModules(ModuleEvent $event)
    {
        $clientId = $event->getParam('client');
        $configListener = $event->getConfigListener();

        if (null === $clientId) {
            throw new RuntimeException('No client domain parameter');
        }

        $clientConfig = $this->getClientConfig($clientId);
        if (null === $clientConfig) {
            throw new RuntimeException('Unknown client');
        }

        $configListener->addConfigStaticPath($clientConfig);
        $clientModules = $this->getClientModules($clientId);

        if (count($clientModules) > 0) {
            $moduleManager = $event->getTarget();
            $moduleManager->setModules(array_merge(
                $moduleManager->getModules(),
                $clientModules
            ));
        }
    }

    /**
     * Sets base path of clients config directory.
     *
     * @param string $basePath
     * @return ClientsConfigListener
     * @throws InvalidArgumentException
     */
    private function setClientsConfigBasePath($basePath)
    {
        if (!is_string($basePath) || !is_dir($basePath)) {
            throw new InvalidArgumentException(sprintf(
                'Directory (%s) does not exists',
                (is_string($basePath) ? $basePath : gettype($basePath))
            ));
        }

        $this->clientsConfigBasePath = rtrim($basePath, '/');

        return $this;
    }

    /**
     * Returns base path of clients config directory
     *
     * @return string
     */
    private function getClientsConfigBasePath()
    {
        return $this->clientsConfigBasePath;
    }

    /**
     * Return url to clients config file, if it exists else return null
     *
     * @param string $clientId
     * @return string
     */
    private function getClientConfig($clientId)
    {
        $file = sprintf(
            '%s/%s.config.php',
            $this->getClientsConfigBasePath(),
            $clientId
        );

        if (is_file($file)) {
            return $file;
        }
    }

    /**
     * Return an array containg the client specific modules to load
     * @param string $clientId
     * @return array
     */
    private function getClientModules($clientId)
    {
        $file = sprintf(
            '%s/%s.modules.php',
            $this->getClientsConfigBasePath(),
            $clientId
        );

        $modules = [];

        if (is_file($file)) {
            $modules = include $file;
        }

        return $modules;
    }
}
