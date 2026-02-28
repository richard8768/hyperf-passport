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

use DateInterval;
use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Tappable\HigherOrderTapProxy;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Psr\Container\ContainerInterface;
use Richard\HyperfPassport\Bridge\AccessTokenRepository;
use Richard\HyperfPassport\Bridge\AuthCodeRepository;
use Richard\HyperfPassport\Bridge\ClientRepository;
use Richard\HyperfPassport\Bridge\PersonalAccessGrant;
use Richard\HyperfPassport\Bridge\RefreshTokenRepository;
use Richard\HyperfPassport\Bridge\ScopeRepository;
use Richard\HyperfPassport\Bridge\UserRepository;

class AuthorizationServerFactory
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected ConfigInterface $config;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function __invoke(): AuthorizationServer|HigherOrderTapProxy
    {
        $tokenExpireDays = new DateInterval('P7D');
        $refreshTokenExpireDays = new DateInterval('P60D');
        $personTokenDays = new DateInterval('P7D');
        $passport = make(Passport::class);
        $passport->setClientUuids($this->config->get('passport.client_uuids', false));
        $passport->tokensExpireIn($this->config->get('passport.token_days', $tokenExpireDays));
        $passport->refreshTokensExpireIn($this->config->get('passport.refresh_token_days', $refreshTokenExpireDays));
        $passport->personalAccessTokensExpireIn($this->config->get('passport.person_token_days', $personTokenDays));
        return tap($this->makeAuthorizationServer(), function (AuthorizationServer $server) use ($passport) {
            $server->setDefaultScope($passport->defaultScope);
            $server->enableGrantType($this->makeAuthCodeGrant(), $passport->tokensExpireIn());
            $server->enableGrantType($this->makeRefreshTokenGrant(), $passport->tokensExpireIn());
            $server->enableGrantType($this->makePasswordGrant(), $passport->tokensExpireIn());
            $server->enableGrantType(new PersonalAccessGrant(), $passport->personalAccessTokensExpireIn());
            $server->enableGrantType(new ClientCredentialsGrant(), $passport->tokensExpireIn());
            if ($passport->implicitGrantEnabled) {
                $server->enableGrantType($this->makeImplicitGrant(), $passport->tokensExpireIn());
            }
            return $server;
        });
    }

    public function makeRefreshTokenGrant(): HigherOrderTapProxy|RefreshTokenGrant
    {
        $repository = make(RefreshTokenRepository::class);
        $passport = make(Passport::class);
        return tap(new RefreshTokenGrant($repository), function ($grant) use ($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    public function makePasswordGrant(): PasswordGrant
    {
        $grant = new PasswordGrant(make(UserRepository::class), make(RefreshTokenRepository::class));
        $passport = make(Passport::class);
        $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());

        return $grant;
    }

    /**
     * Make the authorization service instance.
     */
    public function makeAuthorizationServer(): AuthorizationServer
    {
        return new AuthorizationServer(make(ClientRepository::class), make(AccessTokenRepository::class), make(ScopeRepository::class), $this->makeCryptKey('private'), $this->config->get('passport.key', 'E3Wxizr8gUXuBuyG7CecmGX9E9lbRzdFmqQpG2yP85eDuXzqOj'));
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     */
    protected function makeAuthCodeGrant(): AuthCodeGrant
    {
        $passport = make(Passport::class);
        return tap($this->buildAuthCodeGrant(), function ($grant) use ($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     *
     * @throws Exception
     */
    protected function buildAuthCodeGrant(): AuthCodeGrant
    {
        return new AuthCodeGrant(make(AuthCodeRepository::class), make(RefreshTokenRepository::class), new DateInterval('PT10M'));
    }

    protected function makeImplicitGrant(): ImplicitGrant
    {
        $passport = make(Passport::class);
        return new ImplicitGrant($passport->tokensExpireIn());
    }

    /**
     * Create a CryptKey instance without permissions check.
     */
    protected function makeCryptKey(string $type): CryptKey
    {
        $passport = make(Passport::class);
        $key = str_replace('\n', "\n", file_get_contents($passport->keyPath('oauth-' . $type . '.key')));

        return new CryptKey($key, null, false);
    }
}
