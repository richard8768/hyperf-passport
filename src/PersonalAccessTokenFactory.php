<?php

namespace Richard\HyperfPassport;

use Lcobucci\JWT\Token\Parser as JwtParser;
use Lcobucci\JWT\Encoding\JoseEncoder;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class PersonalAccessTokenFactory
{

    /**
     * The authorization server instance.
     *
     * @var AuthorizationServer
     */
    protected AuthorizationServer $server;

    /**
     * The client repository instance.
     *
     * @var ClientRepository
     */
    protected ClientRepository $clients;

    /**
     * The token repository instance.
     *
     * @var TokenRepository
     */
    protected TokenRepository $tokens;

    /**
     * The JWT token parser instance.
     *
     * @var JwtParser
     *
     * @deprecated This property will be removed in a future Passport version.
     */
    protected JwtParser $jwt;

    /**
     * Create a new personal access token factory instance.
     *
     * @param AuthorizationServer $server
     * @param ClientRepository $clients
     * @param TokenRepository $tokens
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                ClientRepository    $clients,
                                TokenRepository     $tokens)
    {
        $this->jwt = new JwtParser(new JoseEncoder());
        $this->tokens = $tokens;
        $this->server = $server;
        $this->clients = $clients;
    }

    /**
     * Create a new personal access token.
     *
     * @param mixed $userId
     * @param string $name
     * @param array $scopes
     * @return \Richard\HyperfPassport\PersonalAccessTokenResult
     */
    public function make($userId, $name, array $scopes = [], $provider = 'users')
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
            $response['access_token'], $token
        );
    }

    /**
     * Create a request instance for the given client.
     *
     * @param \Richard\HyperfPassport\Client $client
     * @param mixed $userId
     * @param array $scopes
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function createRequest($client, $userId, array $scopes)
    {
        $passport = make(\Richard\HyperfPassport\Passport::class);
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return array
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request)
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request, new Response
        )->getBody()->__toString(), true);
    }

    /**
     * Get the access token instance for the parsed response.
     *
     * @param array $response
     * @return \Richard\HyperfPassport\Token
     */
    protected function findAccessToken(array $response)
    {
        return $this->tokens->find(
            $this->jwt->parse($response['access_token'])->claims()->get('jti')
        );
    }

}
