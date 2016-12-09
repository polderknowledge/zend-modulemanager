# Usage

The ClientConfigListener will modify the application config before the application
is bootstrapped to load client specific configuration.

Add the following config to your application config to enable the usage of this 
listener.

```php
return [
    'clients_config_path' => 'config/clients',
    'service_manager' => [
        'delegators' => [
            'ModuleManager' => [
                'PolderKnowledge\ModuleManager\ClientsConfigDelegatorFactory',
            ],
        ],
    ],
];
```

## Client Config Path

All configuration files should be stored in the directory that is configured in
`clients_config_path`.

## Client Configuration

In order to extend the concatenated config of Zend Framework, you should create a
configuration file for the hostname that your application runs on.

The configuration that is loaded should follow the convention of 
`{hostname}.config.php`. For host `polderknowledge.com` this means that a 
configuration file called `polderknowledge.nl.config.php` should be created.

The file `polderknowledge.nl.config.php` makes it possible to overwrite any 
config setting in a Zend Framework application. This file works the same as 
the configuration files stored in the `config/autoload/` directory meaning that
the configuration is merged into the Config service.

## Client Modules

Beside extending the configuration for a client, it's also possible to load 
additional modules. These modules should be specified in a file called
`{hostname}.modules.php`. So for `polderknowledge.nl` a file called 
`polderknowledge.nl.modules.php` should be created.
