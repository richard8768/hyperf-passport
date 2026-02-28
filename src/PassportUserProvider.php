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

use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Contracts\ExtendUserProvider;

class PassportUserProvider implements ExtendUserProvider
{
    protected array $config;

    protected string $name;

    public function __construct(array $config, string $name)
    {
        $this->config = $config;
        $this->name = $name;
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$identifier]);
    }

    public function retrieveByToken($identifier, string $token): ?Authenticatable
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$identifier, $token]);
    }

    public function retrieveByCredentials($credentials): ?Authenticatable
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$credentials]);
    }

    public function validateCredentials(Authenticatable $user, $credentials): bool
    {
        return $user->getId() === $credentials;
    }

    /**
     * Get the name of the user provider.
     */
    public function getProviderName(): string
    {
        return $this->name;
    }
}
