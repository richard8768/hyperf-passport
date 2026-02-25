<?php

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
     *
     * @var Config
     */
    protected Config $config;

    /**
     * The encrypter implementation.
     *
     * @var DriverInterface
     */
    protected DriverInterface $encrypter;

    /**
     * Create an API token cookie factory instance.
     *
     * @param Config $config
     * @param EncryptionManager $encrypterManager
     * @return void
     */
    public function __construct(Config $config, EncryptionManager $encrypterManager)
    {
        $this->config = $config;
        $this->encrypter = $encrypterManager->getDriver();
    }

    /**
     * Create a new API token cookie.
     *
     * @param mixed $userId
     * @param string $csrfToken
     * @return Cookie
     */
    public function make(mixed $userId, string $csrfToken): Cookie
    {
        $configArray = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($configArray['cookie_lifetime']);
        $passport = make(Passport::class);
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
     *
     * @param mixed $userId
     * @param string $csrfToken
     * @param Carbon $expiration
     * @return string
     */
    protected function createToken(mixed $userId, string $csrfToken, Carbon $expiration): string
    {
        return JWT::encode([
            'sub' => $userId,
            'csrf' => $csrfToken,
            'expiry' => $expiration->getTimestamp(),
        ], $this->encrypter->getKey(),'HS256');
    }

}
