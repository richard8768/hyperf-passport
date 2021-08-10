<?php

namespace Richard\HyperfPassport;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Hyperf\Contract\ConfigInterface as Config;
use HyperfExt\Encryption\EncryptionManager;
use Symfony\Component\HttpFoundation\Cookie;

class ApiTokenCookieFactory {

    /**
     * The configuration repository implementation.
     *
     * @var \Hyperf\Contract\ConfigInterface
     */
    protected $config;

    /**
     * The encrypter implementation.
     *
     * @var \HyperfExt\Encryption\Contract\DriverInterface
     */
    protected $encrypter;

    /**
     * Create an API token cookie factory instance.
     *
     * @param  \Hyperf\Contract\ConfigInterface  $config
     * @param  \HyperfExt\Encryption\EncryptionManager  $encrypter
     * @return void
     */
    public function __construct(Config $config, EncryptionManager $encrypterManager) {
        $this->config = $config;
        $this->encrypter = $encrypterManager->getDriver();
    }

    /**
     * Create a new API token cookie.
     *
     * @param  mixed  $userId
     * @param  string  $csrfToken
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($userId, $csrfToken) {
        $configArray = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($configArray['lifetime']);
        $passport = make(\Richard\HyperfPassport\Passport::class);
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
     * @param  mixed  $userId
     * @param  string  $csrfToken
     * @param  \Carbon\Carbon  $expiration
     * @return string
     */
    protected function createToken($userId, $csrfToken, Carbon $expiration) {
        return JWT::encode([
                    'sub' => $userId,
                    'csrf' => $csrfToken,
                    'expiry' => $expiration->getTimestamp(),
                        ], $this->encrypter->getKey());
    }

}
