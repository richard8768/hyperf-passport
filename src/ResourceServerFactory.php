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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use Psr\Container\ContainerInterface;
use Richard\HyperfPassport\Bridge\AccessTokenRepository;

class ResourceServerFactory
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected ConfigInterface $config;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function __invoke(): ResourceServer
    {
        return new ResourceServer(make(AccessTokenRepository::class), $this->makeCryptKey('public'));
    }

    /**
     * Create a CryptKey instance without permissions check.
     */
    protected function makeCryptKey(string $type): CryptKey
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $key = str_replace('\n', "\n", file_get_contents($passport->keyPath('oauth-' . $type . '.key')));

        return new CryptKey($key, null, false);
    }
}
