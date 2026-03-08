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

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Hyperf\Contract\ConfigInterface as Config;
use Hyperf\HttpMessage\Cookie\Cookie;
use HyperfExt\Encryption\Contract\DriverInterface;
use HyperfExt\Encryption\EncryptionManager;

class ApiTokenCookieFactory
{
    /**
     * The configuration repository implementation.
     */
    protected Config $config;

    /**
     * The encrypter implementation.
     */
    protected DriverInterface $encrypter;

    /**
     * Create an API token cookie factory instance.
     */
    public function __construct(Config $config, EncryptionManager $encrypterManager)
    {
        $this->config = $config;
        $this->encrypter = $encrypterManager->getDriver();
    }

    /**
     * Create a new API token cookie.
     */
    public function make(mixed $userId, string $csrfToken): Cookie
    {
        $configArray = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($configArray['cookie_lifetime']??5 * 60 * 60);
        $passport = \Hyperf\Support\make(Passport::class);
        return new Cookie(
            $passport->cookie(),
            $this->createToken($userId, $csrfToken, $expiration),
            $expiration,
            $configArray['path'],
            $configArray['domain'],
            $configArray['secure'],
            true,
            false,
            $configArray['same_site'] ?? null
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     */
    protected function createToken(mixed $userId, string $csrfToken, Carbon $expiration): string
    {
        return JWT::encode([
            'sub' => $userId,
            'csrf' => $csrfToken,
            'expiry' => $expiration->getTimestamp(),
        ], $this->encrypter->getKey(), 'HS256');
    }
}
