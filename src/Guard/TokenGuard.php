<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Guard;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Stringable\Str;
use HyperfExt\Cookie\CookieValuePrefix;
use HyperfExt\Cookie\Middleware\EncryptCookieMiddleware;
use HyperfExt\Encryption\Contract\DriverInterface as EncryptionDriverInterface;
use HyperfExt\Encryption\EncryptionManager;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\UserProvider;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Contracts\ExtendAuthGuard;
use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;
use Richard\HyperfPassport\TransientToken;
use stdClass;

class TokenGuard implements ExtendAuthGuard
{
    protected array $config;

    protected string $name;

    /**
     * The currently authenticated user.
     */
    protected ?Authenticatable $user;

    /**
     * The resource server instance.
     */
    protected ResourceServer $server;

    /**
     * The user provider implementation.
     */
    protected UserProvider $userProvider;

    /**
     * The token repository instance.
     */
    protected TokenRepository $tokens;

    /**
     * The client repository instance.
     */
    protected ClientRepository $clients;

    /**
     * The encrypter implementation.
     */
    protected EncryptionDriverInterface $encrypter;

    protected RequestInterface $request;

    /**
     * Create a new token guard instance.
     */
    public function __construct(
        array $config,
        string $name,
        ResourceServer $server,
        UserProvider $userProvider,
        TokenRepository $tokens,
        ClientRepository $clients,
        EncryptionManager $encrypterManager,
        RequestInterface $request
    ) {
        $this->config = $config;
        $this->name = $name;
        $this->server = $server;
        $this->userProvider = $userProvider;
        $this->tokens = $tokens;
        $this->clients = $clients;
        $this->encrypter = $encrypterManager->getDriver();
        $this->request = $request;
    }

    /**
     * Get the user for the incoming request.
     */
    public function user(): ?Authenticatable
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $passport = \Hyperf\Support\make(Passport::class);
        if ($this->bearerToken($this->request)) {
            return $this->user = $this->authenticateViaBearerToken($this->request);
        }

        if ($this->request->cookie($passport->cookie())) {
            return $this->user = $this->authenticateViaCookie($this->request);
        }
        return null;
    }

    /**
     * Get the client for the incoming request.
     *
     * @return null|Client
     */
    public function client()
    {
        $passport = \Hyperf\Support\make(Passport::class);
        if ($this->bearerToken($this->request)) {
            if (! $psr = $this->getPsrRequestViaBearerToken($this->request)) {
                return null;
            }

            return $this->clients->findActive(
                $psr->getAttribute('oauth_client_id')
            );
        }

        if ($this->request->cookie($passport->cookie()) && $token = $this->getTokenViaCookie($this->request)) {
            return $this->clients->findActive($token['aud']);
        }
        return null;
    }

    /**
     * Determine if the cookie contents should be serialized.
     */
    public static function serialized(): bool
    {
        return EncryptCookieMiddleware::serialized('XSRF-TOKEN');
    }

    /**
     * Get the bearer token from the request headers.
     */
    public function bearerToken(RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProvider(): UserProvider
    {
        return $this->userProvider;
    }

    public function login(Authenticatable $user)
    {
        $this->user = $user;
        return $this;
    }

    public function logout()
    {
        $this->user = null;
        return $this;
    }

    /**
     * Determine if the guard has a user instance.
     */
    public function hasUser(): bool
    {
        return ! is_null($this->user);
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): null|int|string
    {
        if ($this->user()) {
            return $this->user()->getKey();
        }
        return null;
    }

    public function validate(array $credentials = []): bool
    {
        $currentUser = $this->userProvider->retrieveByCredentials($credentials);
        return $this->hasValidCredentials($currentUser, $credentials);
    }

    /**
     * Determine if the requested provider matches the client's provider.
     */
    protected function hasValidProvider(): bool
    {
        $client = $this->client();

        if ($client && ! $client->provider) {
            return true;
        }

        return $client && $client->provider === $this->userProvider->getProviderName();
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     */
    protected function authenticateViaBearerToken(RequestInterface $request): mixed
    {
        if (! $psr = $this->getPsrRequestViaBearerToken($request)) {
            return null;
        }

        if (! $this->hasValidProvider($request)) {
            return null;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $currentUser = $this->userProvider->retrieveById(
            $psr->getAttribute('oauth_user_id') ?: null
        );

        if (! $currentUser) {
            return null;
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
            return null;
        }

        return $token ? $currentUser->withAccessToken($token) : null;
    }

    /**
     * Authenticate and get the incoming PSR-7 request via the Bearer token.
     */
    protected function getPsrRequestViaBearerToken(RequestInterface $request): ?ServerRequestInterface
    {
        try {
            return $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            $request->header('Authorization', '');
            throw new PassportException(
                $e->getMessage(),
                $this,
                $e
            );
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     */
    protected function authenticateViaCookie(RequestInterface $request): mixed
    {
        if (! $token = $this->getTokenViaCookie($request)) {
            return null;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        if ($currentUser = $this->userProvider->retrieveById($token['sub'])) {
            return $currentUser->withAccessToken(new TransientToken());
        }
        return null;
    }

    /**
     * Get the token cookie via the incoming request.
     */
    protected function getTokenViaCookie(RequestInterface $request): ?array
    {
        $passport = \Hyperf\Support\make(Passport::class);
        // If we need to retrieve the token from the cookie, it'll be encrypted , so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return null;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! $passport->ignoreCsrfToken && (! $this->validCsrf($token, $request)
                || time() >= $token['expiry'])) {
            return null;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     */
    protected function decodeJwtTokenCookie(RequestInterface $request): array
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $headers = new stdClass();
        return (array) JWT::decode(
            CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie($passport->cookie()), $passport->unserializesCookies)),
            new Key($this->encrypter->getKey(), 'HS256'),
            $headers
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     */
    protected function validCsrf(array $token, RequestInterface $request): bool
    {
        return isset($token['csrf']) && hash_equals(
            $token['csrf'],
            (string) $this->getTokenFromRequest($request)
        );
    }

    /**
     * Get the CSRF token from the request.
     */
    protected function getTokenFromRequest(RequestInterface $request): string
    {
        $token = $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
        }

        return $token;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     */
    protected function hasValidCredentials(Authenticatable $user, array $credentials): bool
    {
        return ! is_null($user) && $this->userProvider->validateCredentials($user, $credentials);
    }
}
