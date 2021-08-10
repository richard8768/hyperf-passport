<?php

declare(strict_types=1);

namespace Richard\HyperfPassport;

use Hyperf\Contract\ConfigInterface;
use Qbhy\HyperfAuth\Exception\GuardException;
use Qbhy\HyperfAuth\Exception\UserProviderException;
use Qbhy\HyperfAuth\AuthManager as QbhyAuthManager;
use Qbhy\HyperfAuth\AuthGuard;
use Qbhy\HyperfAuth\UserProvider;
use Hyperf\Di\Annotation\Inject;

class AuthManager extends QbhyAuthManager {

    /**
     * @Inject
     * @var \Hyperf\HttpServer\Contract\RequestInterface
     */
    protected $serverRequest;

    /**
     * @throws GuardException
     * @throws UserProviderException
     */
    public function guard(?string $name = null): AuthGuard {
        $provider = '';
        $clentId = $this->serverRequest->header('x-client-id') ?: ($this->serverRequest->input('X-Client-Id') ?: $this->serverRequest->input('x-client-id'));
        if (!empty($clentId)) {
            $clients = make(\Richard\HyperfPassport\ClientRepository::class);
            $clientInfo = $clients->findActive($clentId);
            $tmpProvider = (!empty($clientInfo)) ? $clientInfo->provider : '';
            $configProviders = $this->config['providers'];
            $provider = (!empty($tmpProvider) && $configProviders[$tmpProvider]) ? $tmpProvider : '';
        }

        $name = $name ?? $this->defaultGuard();
        if (empty($this->config['guards'][$name])) {
            throw new GuardException("Does not support this driver: {$name}");
        }

        $config = $this->config['guards'][$name];
        if (!empty($provider)) {
            $config['provider'] = $provider;
        }

        $userProvider = $this->provider($config['provider'] ?? $this->defaultDriver);

        return make(
                $config['driver'],
                compact('name', 'config', 'userProvider')
        );
    }

    /**
     * @throws UserProviderException
     */
    public function provider(?string $name = null): UserProvider {
        $name = $name ?? $this->defaultProvider();
        if (empty($this->config['providers'][$name])) {
            throw new UserProviderException("Does not support this provider: {$name}");
        }

        $config = $this->config['providers'][$name];

        return make(
                $config['driver'],
                [
                    'config' => $config,
                    'name' => $name,
                ]
        );
    }

}
