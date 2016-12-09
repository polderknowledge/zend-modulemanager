<?php
/**
 * Polder Knowledge / ModuleManager (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/modulemanager for the canonical source repository
 * @copyright Copyright (c) 2016 Polder Knowledge (https://polderknowledge.com)
 * @license https://github.com/polderknowledge/modulemanager/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\ModuleManager;

use Interop\Container\ContainerInterface;
use RuntimeException;
use Zend\Console\Console;
use Zend\Http\PhpEnvironment\Request;
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ClientsConfigDelegatorFactory implements DelegatorFactoryInterface
{
    const CONSOLE_DOMAIN_PARAMNAME = '--client=';

    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $clientId = $this->getClientId();

        if (null === $clientId) {
            throw new RuntimeException('Could not determine client domain');
        }

        $this->adjustApplicationConfig($container, $clientId);

        $moduleManager = call_user_func($callback);

        $event = $moduleManager->getEvent();
        $event->setParam('client', $clientId);

        $configuration = $container->get('ApplicationConfig');

        $clientsConfigPath = false;
        if (isset($configuration['clients_config_path'])) {
            $clientsConfigPath = $configuration['clients_config_path'];
        }

        $clientConfigListener = new ClientsConfigListener($clientsConfigPath);
        $clientConfigListener->attach($moduleManager->getEventManager());

        return $moduleManager;
    }

    public function createDelegatorWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName, $callback)
    {
        return $this->__invoke($serviceLocator, $requestedName, $callback, []);
    }

    /**
     * Return current client identifier
     *
     * If request is a console request, the identifier must be passed as argument and
     * deleted after parsing. Zend\Console\Router has no knowledge of this special
     * parameter and returns a route error if not deleted
     *
     * In case of an HTTP request, get the host of the request uri
     *
     * @return string client identifier
     */
    private function getClientId()
    {
        $domain = null;

        if (Console::isConsole()) {
            $requestParams = $_SERVER['argv'];

            foreach ($requestParams as $key => $param) {
                $pos = strpos($param, self::CONSOLE_DOMAIN_PARAMNAME);

                if (0 === $pos) {
                    $domain = substr($param, strlen(self::CONSOLE_DOMAIN_PARAMNAME));
                    unset($_SERVER['argv'][$key]);
                    break;
                }
            }
        } else {
            $request = new Request;
            $domain = $request->getUri()->getHost();
        }

        return $domain;
    }

    /**
     * Updates config_cache_key and module_map_cache_key with client identifier.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $clientId
     */
    private function adjustApplicationConfig(ServiceLocatorInterface $serviceLocator, $clientId)
    {
        $configuration = $serviceLocator->get('ApplicationConfig');
        $configuration['module_listener_options']['config_cache_key'] = $clientId;
        $configuration['module_listener_options']['module_map_cache_key'] = $clientId;

        $serviceLocator->setAllowOverride(true);
        $serviceLocator->setService('ApplicationConfig', $configuration);
        $serviceLocator->setAllowOverride(false);
    }
}
