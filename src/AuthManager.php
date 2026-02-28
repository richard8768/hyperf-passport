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

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\AuthGuard;
use Qbhy\HyperfAuth\AuthManager as QbhyAuthManager;
use Qbhy\HyperfAuth\Exception\GuardException;
use Qbhy\HyperfAuth\Exception\UserProviderException;
use Qbhy\HyperfAuth\UserProvider;

class AuthManager extends QbhyAuthManager
{
    #[Inject]
    protected RequestInterface $serverRequest;

    /**
     * @throws GuardException
     * @throws UserProviderException
     */
    public function guard(?string $name = null): AuthGuard
    {
        $provider = '';
        $headerClientId = $this->serverRequest->header('X-Client-Id') ?? $this->serverRequest->header('x-client-id');
        $inputClientId = $this->serverRequest->input('X-Client-Id') ?? $this->serverRequest->input('x-client-id');
        $clientId = (! empty($headerClientId)) ? $headerClientId : $inputClientId;
        if (! empty($clientId)) {
            $clients = \Hyperf\Support\make(ClientRepository::class);
            $clientInfo = $clients->findActive($clientId);
            $tmpProvider = $clientInfo->provider ?? '';
            $configProviders = $this->config['providers'];
            $provider = ! empty($tmpProvider) && $configProviders[$tmpProvider] ? $tmpProvider : '';
        }

        $name = $name ?? $this->defaultGuard();
        if (empty($this->config['guards'][$name])) {
            throw new GuardException('Does not support this driver: ' . $name);
        }

        $config = $this->config['guards'][$name];
        if (! empty($provider)) {
            $config['provider'] = $provider;
        }

        $userProvider = $this->provider($config['provider'] ?? $this->defaultDriver);
        return make($config['driver'], compact('name', 'config', 'userProvider'));
    }

    /**
     * @throws UserProviderException
     */
    public function provider(?string $name = null): UserProvider
    {
        $name = $name ?? $this->defaultProvider();
        if (empty($this->config['providers'][$name])) {
            throw new UserProviderException('Does not support this provider: ' . $name);
        }

        $config = $this->config['providers'][$name];
        return make($config['driver'], ['config' => $config, 'name' => $name]);
    }
}
