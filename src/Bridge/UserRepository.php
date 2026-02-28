<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Bridge;

use HyperfExt\Hashing\HashManager as HashingHashManager;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Richard\HyperfPassport\Exception\PassportException;

class UserRepository implements UserRepositoryInterface
{
    protected HashingHashManager $hasher;

    public function __construct(HashingHashManager $hasher)
    {
        $this->hasher = $hasher;
    }

    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): null|User|UserEntityInterface
    {
        $provider = $clientEntity->provider ?: config('auth.guards.passport.provider');

        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw new PassportException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'findAndValidateForPassport')) {
            $user = (new $model())->findAndValidateForPassport($username, $password);

            if (! $user) {
                return null;
            }

            return new User($user->getKey());
        }

        if (method_exists($model, 'findForPassport')) {
            $user = (new $model())->findForPassport($username);
        } else {
            $user = (new $model())->where('email', $username)->first();
        }

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return null;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return null;
        }

        return new User($user->getKey());
    }
}
