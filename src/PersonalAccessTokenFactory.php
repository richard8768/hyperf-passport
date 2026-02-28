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

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class PersonalAccessTokenFactory
{
    /**
     * The authorization server instance.
     */
    protected AuthorizationServer $server;

    /**
     * The client repository instance.
     */
    protected ClientRepository $clients;

    /**
     * The token repository instance.
     */
    protected TokenRepository $tokens;

    /**
     * The JWT token parser instance.
     *
     * @deprecated this property will be removed in a future Passport version
     */
    protected JwtParser $jwt;

    /**
     * Create a new personal access token factory instance.
     */
    public function __construct(
        AuthorizationServer $server,
        ClientRepository $clients,
        TokenRepository $tokens
    ) {
        $this->jwt = new JwtParser(new JoseEncoder());
        $this->tokens = $tokens;
        $this->server = $server;
        $this->clients = $clients;
    }

    /**
     * Create a new personal access token.
     */
    public function make(mixed $userId, string $name, array $scopes = [], string $provider = 'users'): PersonalAccessTokenResult
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($this->clients->personalAccessClient($provider), $userId, $scopes)
        );

        $token = tap($this->findAccessToken($response), function ($token) use ($userId, $name) {
            $this->tokens->save($token->forceFill([
                'user_id' => $userId,
                'name' => $name,
            ]));
        });

        return new PersonalAccessTokenResult(
            $response['access_token'],
            $token
        );
    }

    /**
     * Create a request instance for the given client.
     */
    protected function createRequest(Client $client, mixed $userId, array $scopes): ServerRequestInterface
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $secret = $passport->hashesClientSecrets ? $this->clients->getPersonalAccessClientSecret() : $client->secret;

        return (new ServerRequest('POST', 'not-important'))->withParsedBody([
            'grant_type' => 'personal_access',
            'client_id' => $client->id,
            'client_secret' => $secret,
            'user_id' => $userId,
            'scope' => implode(' ', $scopes),
        ]);
    }

    /**
     * Dispatch the given request to the authorization server.
     *
     * @throws OAuthServerException
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request): array
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request,
            new Response()
        )->getBody()->__toString(), true);
    }

    /**
     * Get the access token instance for the parsed response.
     */
    protected function findAccessToken(array $response): Token
    {
        return $this->tokens->find(
            $this->jwt->parse($response['access_token'])->claims()->get('jti')
        );
    }
}
