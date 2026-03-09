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

use Hyperf\Database\Model\Collection;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Request;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\RefreshTokenRepository;
use Richard\HyperfPassport\TokenRepository;

class AuthorizedAccessTokenController
{
    /**
     * The token repository implementation.
     */
    protected TokenRepository $tokenRepository;

    /**
     * The refresh token repository implementation.
     */
    protected RefreshTokenRepository $refreshTokenRepository;

    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     */
    public function __construct(TokenRepository $tokenRepository, RefreshTokenRepository $refreshTokenRepository, AuthManager $auth)
    {
        $this->tokenRepository = $tokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all the authorized tokens for the authenticated user.
     */
    public function forUser(Request $request): Collection|\Hyperf\Collection\Collection
    {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return ! $token->client->firstParty() && ! $token->revoked;
        })->values();
    }

    /**
     * Delete the given token.
     *
     * @return Response
     */
    public function destroy(Request $request)
    {
        $tokenId = $request->route('token_id');
        if (empty($tokenId)) {
            throw new PassportException('token_id is required');
        }

        $user = $this->auth->guard('passport')->user();
        $token = $this->tokenRepository->findForUser(
            $tokenId,
            $user->getKey()
        );

        if (is_null($token)) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new SwooleStream(''));
        }

        $token->revoke();

        $this->refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

        $response = new Response();
        return $response->withStatus(204)->withBody(new SwooleStream(''));
    }
}
