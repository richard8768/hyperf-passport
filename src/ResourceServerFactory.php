<?php

declare(strict_types=1);

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;

class ResourceServerFactory {

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param  \Psr\Container\ContainerInterface  $container
     * @param  \Hyperf\Contract\ConfigInterface  $config
     * @return void
     */
    public function __construct(ContainerInterface $container, ConfigInterface $config) {
        $this->container = $container;
        $this->config = $config;
    }

    public function __invoke() {
        return new ResourceServer(
                make(\Richard\HyperfPassport\Bridge\AccessTokenRepository::class),
                $this->makeCryptKey('public')
        );
    }

    /**
     * Create a CryptKey instance without permissions check.
     *
     * @param  string  $key
     * @return \League\OAuth2\Server\CryptKey
     */
    protected function makeCryptKey($type) {
        $key = str_replace('\\n', "\n", $this->config->get('passport.' . $type . '_key'));
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if (!$key) {
            $key = 'file://' . $passport->keyPath('oauth-' . $type . '.key');
        }

        return new CryptKey($key, null, false);
    }

}
