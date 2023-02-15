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
use Richard\HyperfPassport\Bridge\ClientRepository;
use Richard\HyperfPassport\Bridge\RefreshTokenRepository;
use Richard\HyperfPassport\Bridge\PersonalAccessGrant;
use Richard\HyperfPassport\Bridge\AccessTokenRepository;
use Richard\HyperfPassport\Bridge\ScopeRepository;
use Richard\HyperfPassport\Bridge\AuthCodeRepository;
use Richard\HyperfPassport\Bridge\UserRepository;

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
     * @param ContainerInterface $container
     * @param ConfigInterface $config
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
        return tap($this->makeAuthorizationServer(), function (AuthorizationServer $server)use($passport) {
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
                    new PersonalAccessGrant, $passport->personalAccessTokensExpireIn()
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
     * @return AuthCodeGrant
     */
    protected function makeAuthCodeGrant() {
        $passport = make(Passport::class);
        return tap($this->buildAuthCodeGrant(), function ($grant) use($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     *
     * @return AuthCodeGrant
     */
    protected function buildAuthCodeGrant() {
        return new AuthCodeGrant(
                make(AuthCodeRepository::class),
                make(RefreshTokenRepository::class),
                new \DateInterval('PT10M')
        );
    }

    public function makeRefreshTokenGrant() {
        $repository = make(RefreshTokenRepository::class);
        $passport = make(Passport::class);
        return tap(new RefreshTokenGrant($repository), function ($grant) use($passport) {
            $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());
        });
    }

    public function makePasswordGrant() {
        $grant = new PasswordGrant(
                make(UserRepository::class),
                make(RefreshTokenRepository::class)
        );
        $passport = make(Passport::class);
        $grant->setRefreshTokenTTL($passport->refreshTokensExpireIn());

        return $grant;
    }

    protected function makeImplicitGrant() {
        $passport = make(Passport::class);
        return new ImplicitGrant($passport->tokensExpireIn());
    }

    /**
     * Make the authorization service instance.
     *
     * @return AuthorizationServer
     */
    public function makeAuthorizationServer() {
        return new AuthorizationServer(
                make(ClientRepository::class),
                make(AccessTokenRepository::class),
                make(ScopeRepository::class),
                $this->makeCryptKey('private'),
                $this->config->get('passport.key', 'E3Wxizr8gUXuBuyG7CecmGX9E9lbRzdFmqQpG2yP85eDuXzqOj')
        );
    }

    /**
     * Create a CryptKey instance without permissions check.
     *
     * @param  string  $type
     * @return CryptKey
     */
    protected function makeCryptKey($type) {
        $passport = make(Passport::class);
        $key = str_replace('\\n', "\n", file_get_contents($passport->keyPath('oauth-'.$type.'.key')));

        return new CryptKey($key, null, false);
    }

}
