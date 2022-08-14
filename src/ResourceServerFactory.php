<?php

declare(strict_types=1);

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\CryptKey;
use Richard\HyperfPassport\Bridge\AccessTokenRepository;

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
     * @param ContainerInterface $container
     * @param ConfigInterface $config
     * @return void
     */
    public function __construct(ContainerInterface $container, ConfigInterface $config) {
        $this->container = $container;
        $this->config = $config;
    }

    public function __invoke() {
        return new ResourceServer(
                make(AccessTokenRepository::class),
                $this->makeCryptKey('public')
        );
    }

    /**
     * Create a CryptKey instance without permissions check.
     *
     * @param  string  $type
     * @return CryptKey
     */
    protected function makeCryptKey($type) {
        $passport = make(Passport::class);
        $key = str_replace('\\n', "\n", file_get_contents($passport->keyPath('oauth-'.$type.'.key')));

        return new CryptKey($key, null, false);
    }

}
