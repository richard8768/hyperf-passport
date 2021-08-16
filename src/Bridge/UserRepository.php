<?php

namespace Richard\HyperfPassport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use RuntimeException;
use Hyperf\Di\Annotation\Inject;
use HyperfExt\Hashing\HashManager as HashingHashManager;

class UserRepository implements UserRepositoryInterface {

    protected $hasher;

    public function __construct(HashingHashManager $hasher) {
        $this->hasher = $hasher;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity) {
        $provider = $clientEntity->provider ?: config('auth.guards.passport.provider');

        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw new \Richard\HyperfPassport\Exception\PassportException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'findAndValidateForPassport')) {
            $user = (new $model)->findAndValidateForPassport($username, $password);

            if (!$user) {
                return;
            }

            return new User($user->getKey());
        }

        if (method_exists($model, 'findForPassport')) {
            $user = (new $model)->findForPassport($username);
        } else {
            $user = (new $model)->where('email', $username)->first();
        }

        if (!$user) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (!$user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (!$this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }

        return new User($user->getKey());
    }

}
