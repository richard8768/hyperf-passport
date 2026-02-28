<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Richard\HyperfPassport\Console\ClientCommand;
use Richard\HyperfPassport\Console\HashCommand;
use Richard\HyperfPassport\Console\InstallCommand;
use Richard\HyperfPassport\Console\KeysCommand;
use Richard\HyperfPassport\Console\PurgeCommand;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                \Hyperf\HttpServer\Router\DispatcherFactory::class => DispatcherFactory::class,
                \Hyperf\Session\Session::class => Session::class,
                \Qbhy\HyperfAuth\AuthManager::class => AuthManager::class,
                AuthorizationServer::class => AuthorizationServerFactory::class,
                ResourceServer::class => ResourceServerFactory::class,
            ],
            'listeners' => [
            ],
            'commands' => [
                InstallCommand::class,
                ClientCommand::class,
                KeysCommand::class,
                HashCommand::class,
                PurgeCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'collectors' => [
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for passport.',
                    'source' => __DIR__ . '/../publish/passport.php',
                    'destination' => BASE_PATH . '/config/autoload/passport.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'The migration for passport.',
                    'source' => __DIR__ . '/../migrations',
                    'destination' => BASE_PATH . '/migrations',
                ],
                [
                    'id' => 'view',
                    'description' => 'The view for passport.',
                    'source' => __DIR__ . '/../resources/views',
                    'destination' => BASE_PATH . '/storage/view',
                ],
            ],
        ];
    }
}
