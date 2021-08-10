<?php

declare(strict_types=1);

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class AuthorizationServerFactory {

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @param  \Psr\Container\ContainerInterface  $container
     * @param  \Hyperf\Contract\ConfigInterface  $config
     * @return void
     */
    public function __construct(ContainerInterface $container, ConfigInterface $config) {
        $this->container = $container;
        $this->config = $config;
    }

    public function __invoke() {
        $tokenExpireDays = new \DateInterval('P7D');
        $refreashTokenExpireDays = new \DateInterval('P60D');
        $personTokenDays = new \DateInterval('P7D');
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $passport->setClientUuids($this->config->get('passport.client_uuids', false));
        $passport->tokensExpireIn($this->config->get('passport.token_days', $tokenExpireDays));
        $passport->refreshTokensExpireIn($this->config->get('passport.refresh_token_days', $refreashTokenExpireDays));
        $passport->personalAccessTokensExpireIn($this->config->get('passport.person_token_days', $personTokenDays));
        return tap($this->makeAuthorizationServer(), function (\League\OAuth2\Server\AuthorizationServer $server)use($passport) {
            $server->setDefaultScope($passport->defaultScope);

            $server->enableGrantType(
                    $this->makeAuthCodeGrant(), $passport->tokensExpireIn()
            );

            $server->enableGrantType(
                    $this->makeRefreshTokenGrant(), $passport->tokensExpireIn()
            );

            $server->enableGrantType(
                    $this->makePasswordGrant(), $passport->tokensExpireIn()
            );

            $server->enableGrantType(
                    new \Richard\HyperfPassport\Bridge\PersonalAccessGrant, $passport->personalAccessTokensExpireIn()
            );

            $server->enableGrantType(
                    new ClientCredentialsGrant, $passport->tokensExpireIn()
            );

            if ($passport->implicitGrantEnabled) {
                $server->enableGrantType(
                        $this->makeImplicitGrant(), $passport->tokensExpireIn()
                );
            }
            return $server;
        });
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     *
     * @return \League\OAuth2\Server\Grant\AuthCodeGrant
     */
    protected function makeAuthCodeGrant() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return tap($this->buildAuthCodeGrant(), function ($grant) use($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     *
     * @return \League\OAuth2\Server\Grant\AuthCodeGrant
     */
    protected function buildAuthCodeGrant() {
        return new AuthCodeGrant(
                make(\Richard\HyperfPassport\Bridge\AuthCodeRepository::class),
                make(\Richard\HyperfPassport\Bridge\RefreshTokenRepository::class),
                new \DateInterval('PT10M')
        );
    }

    public function makeRefreshTokenGrant() {
        $repository = make(\Richard\HyperfPassport\Bridge\RefreshTokenRepository::class);
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return tap(new RefreshTokenGrant($repository), function ($grant) use($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    public function makePasswordGrant() {
        $grant = new PasswordGrant(
                make(\Richard\HyperfPassport\Bridge\UserRepository::class),
                make(\Richard\HyperfPassport\Bridge\RefreshTokenRepository::class)
        );
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());

        return $grant;
    }

    protected function makeImplicitGrant() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return new ImplicitGrant($passport->tokensExpireIn());
    }

    /**
     * Make the authorization service instance.
     *
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function makeAuthorizationServer() {
        return new AuthorizationServer(
                make(\Richard\HyperfPassport\Bridge\ClientRepository::class),
                make(\Richard\HyperfPassport\Bridge\AccessTokenRepository::class),
                make(\Richard\HyperfPassport\Bridge\ScopeRepository::class),
                $this->makeCryptKey('private'),
                $this->config->get('passport.key', 'E3Wxizr8gUXuBuyG7CecmGX9E9lbRzdFmqQpG2yP85eDuXzqOj')
        );
    }

    /**
     * Create a CryptKey instance without permissions check.
     *
     * @param  string  $key
     * @return \League\OAuth2\Server\CryptKey
     */
    protected function makeCryptKey($type) {
        $key = str_replace('\\n', "\n", $this->config->get('passport.' . $type . '_key'));
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if (!$key) {
            $key = 'file://' . $passport->keyPath('oauth-' . $type . '.key');
        }

        return new CryptKey($key, null, false);
    }

}
