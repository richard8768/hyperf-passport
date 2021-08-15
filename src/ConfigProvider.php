<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

class ConfigProvider {

    public function __invoke(): array {
        return [
            'dependencies' => [
                \Hyperf\HttpServer\Router\DispatcherFactory::class => DispatcherFactory::class,
                \Hyperf\Session\Session::class => Session::class,
                \Qbhy\HyperfAuth\AuthManager::class => AuthManager::class,
                \League\OAuth2\Server\AuthorizationServer::class => AuthorizationServerFactory::class,
                \League\OAuth2\Server\ResourceServer::class => ResourceServerFactory::class,
            ],
            'listeners' => [
            //
            ],
            'commands' => [
                \Richard\HyperfPassport\Console\InstallCommand::class,
                \Richard\HyperfPassport\Console\ClientCommand::class,
                \Richard\HyperfPassport\Console\KeysCommand::class,
                \Richard\HyperfPassport\Console\HashCommand::class,
                \Richard\HyperfPassport\Console\PurgeCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'collectors' => [
                    //
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
