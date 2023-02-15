<?php

namespace Richard\HyperfPassport;

use Richard\HyperfPassport\Contracts\ExtendUserProvider;
use Qbhy\HyperfAuth\Authenticatable;

class PassportUserProvider implements ExtendUserProvider
{

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var string
     */
    protected string $name;

    public function __construct(array $config, string $name)
    {
        $this->config = $config;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$identifier, $token]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials($credentials): null|Authenticatable
    {
        return call_user_func_array([$this->config['model'], 'retrieveById'], [$credentials]);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, $credentials): bool
    {
        return $user->getId() === $credentials;
    }

    /**
     * Get the name of the user provider.
     *
     * @return string
     */
    public function getProviderName()
    {
        return $this->name;
    }

}
