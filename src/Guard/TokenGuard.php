<?php

namespace Richard\HyperfPassport\Guard;

use Exception;
use Firebase\JWT\JWT;
use HyperfExt\Encryption\EncryptionManager;
use HyperfExt\Cookie\CookieValuePrefix;
use HyperfExt\Cookie\Middleware\EncryptCookieMiddleware;
use Hyperf\HttpServer\Request;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;
use Richard\HyperfPassport\TransientToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Hyperf\Utils\Str;
use Richard\HyperfPassport\Contracts\ExtendAuthGuard;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\UserProvider;

class TokenGuard implements ExtendAuthGuard {

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $name;

    /**
     * The currently authenticated user.
     *
     * @var \Qbhy\HyperfAuth\Authenticatable
     */
    protected $user;

    /**
     * The resource server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * The user provider implementation.
     *
     * @var \Qbhy\HyperfAuth\UserProvider
     */
    protected $userProvider;

    /**
     * The token repository instance.
     *
     * @var \Richard\HyperfPassport\TokenRepository
     */
    protected $tokens;

    /**
     * The client repository instance.
     *
     * @var \Richard\HyperfPassport\ClientRepository
     */
    protected $clients;

    /**
     * The encrypter implementation.
     *
     * @var \HyperfExt\Encryption\EncryptionManager
     */
    protected $encrypter;
    protected $request;

    /**
     * Create a new token guard instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @param  \Qbhy\HyperfAuth\UserProvider  $userProvider
     * @param  \Richard\HyperfPassport\TokenRepository  $tokens
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @param  \HyperfExt\Encryption\EncryptionManager  $encrypterManager
     * @return void
     */
    public function __construct(
            array $config,
            string $name,
            UserProvider $userProvider,
            RequestInterface $request,
            ResourceServer $server,
            TokenRepository $tokens,
            ClientRepository $clients,
            EncryptionManager $encrypterManager
    ) {
        $this->server = $server;
        $this->userProvider = $userProvider;
        $this->tokens = $tokens;
        $this->clients = $clients;
        $this->encrypter = $encrypterManager->getDriver();
        $this->request = $request;
        $this->config = $config;
        $this->name = $name;
    }

    /**
     * Determine if the requested provider matches the client's provider.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return bool
     */
    protected function hasValidProvider(Request $request) {
        $client = $this->client($request);

        if ($client && !$client->provider) {
            return true;
        }

        return $client && $client->provider === $this->userProvider->getProviderName();
    }

    /**
     * Get the user for the incoming request.
     *
     * @return mixed
     */
    public function user(): ?Authenticatable {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if ($this->bearerToken($this->request)) {
            return $this->authenticateViaBearerToken($this->request);
        } elseif ($this->request->cookie($passport->cookie())) {
            return $this->authenticateViaCookie($this->request);
        }
        return null;
    }

    /**
     * Get the client for the incoming request.
     *
     * @return mixed
     */
    public function client() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if ($this->bearerToken($this->request)) {
            if (!$psr = $this->getPsrRequestViaBearerToken($this->request)) {
                return;
            }

            return $this->clients->findActive(
                            $psr->getAttribute('oauth_client_id')
            );
        } elseif ($this->request->cookie($passport->cookie())) {
            if ($token = $this->getTokenViaCookie($this->request)) {
                return $this->clients->findActive($token['aud']);
            }
        }
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return mixed
     */
    protected function authenticateViaBearerToken(Request $request) {
        if (!$psr = $this->getPsrRequestViaBearerToken($request)) {
            return;
        }

        if (!$this->hasValidProvider($request)) {
            return;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $user = $this->userProvider->retrieveById(
                $psr->getAttribute('oauth_user_id') ?: null
        );

        if (!$user) {
            return;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        $token = $this->tokens->find(
                $psr->getAttribute('oauth_access_token_id')
        );

        $clientId = $psr->getAttribute('oauth_client_id');

        // Finally, we will verify if the client that issued this token is still valid and
        // its tokens may still be used. If not, we will bail out since we don't want a
        // user to be able to send access tokens for deleted or revoked applications.
        if ($this->clients->revoked($clientId)) {
            return;
        }

        return $token ? $user->withAccessToken($token) : null;
    }

    /**
     * Authenticate and get the incoming PSR-7 request via the Bearer token.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getPsrRequestViaBearerToken(Request $request) {
        try {
            return $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            $newException = new \Richard\HyperfPassport\Exception\PassportException(
                    $e->getMessage(),
                    $this,
                    $e
            );
            throw $newException;
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return mixed
     */
    protected function authenticateViaCookie(Request $request) {
        if (!$token = $this->getTokenViaCookie($request)) {
            return;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        if ($user = $this->userProvider->retrieveById($token['sub'])) {
            return $user->withAccessToken(new TransientToken);
        }
    }

    /**
     * Get the token cookie via the incoming request.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return mixed
     */
    protected function getTokenViaCookie(Request $request) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (!$passport->ignoreCsrfToken && (!$this->validCsrf($token, $request) ||
                time() >= $token['expiry'])) {
            return;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return array
     */
    protected function decodeJwtTokenCookie(Request $request) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return (array) JWT::decode(
                        CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie($passport->cookie()), $passport->unserializesCookies)),
                        $this->encrypter->getKey(),
                        ['HS256']
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array  $token
     * @param  \Hyperf\HttpServer\Request  $request
     * @return bool
     */
    protected function validCsrf(array $token, Request $request) {
        return isset($token['csrf']) && hash_equals(
                        $token['csrf'], (string) $this->getTokenFromRequest($request)
        );
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return string
     */
    protected function getTokenFromRequest(Request $request) {
        $token = $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
        }

        return $token;
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @return bool
     */
    public static function serialized() {
        return EncryptCookieMiddleware::serialized('XSRF-TOKEN');
    }

    /**
     * Get the bearer token from the request headers.
     *
     * @return string|null
     */
    public function bearerToken(Request $request) {

        $arrHeader = $request->getHeader('Authorization');
        $header = (!empty($arrHeader)) ? $arrHeader[0] : '';
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }

        if ($request->has('token')) {
            return $request->input('token');
        }

        return null;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getProvider(): UserProvider {
        return $this->userProvider;
    }

    public function login(Authenticatable $user) {
        \Hyperf\Utils\Context::set('user', $user);
        return $this;
    }

    public function logout() {
        \Hyperf\Utils\Context::set('user', null);
        return $this;
    }

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser() {
        return \Hyperf\Utils\Context::has('user');
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool {
        return !$this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id() {
        if ($this->user()) {
            return $this->user()->getKey();
        }
    }

    public function validate(array $credentials = []): bool {
        $user = $this->userProvider->retrieveByCredentials($credentials);
        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials(Authenticatable $user, array $credentials) {
        $validated = !is_null($user) && $this->userProvider->validateCredentials($user, $credentials);
        return $validated;
    }

}
