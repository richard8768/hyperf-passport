<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpMessage\Server\Response;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Richard\HyperfPassport\TokenRepository;

class AccessTokenController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     */
    protected AuthorizationServer $server;

    /**
     * The token repository instance.
     */
    protected TokenRepository $tokens;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        AuthorizationServer $server,
        TokenRepository $tokens
    ) {
        $this->server = $server;
        $this->tokens = $tokens;
    }

    /**
     * Authorize a client to access the user's account.
     */
    public function issueToken(ServerRequestInterface $request): Response
    {
        return $this->withErrorHandling(function () use ($request) {
            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response())
            );
        });
    }
}
