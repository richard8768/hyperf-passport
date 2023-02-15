<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Database\Model\Collection;
use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\RefreshTokenRepository;
use Richard\HyperfPassport\TokenRepository;
use Qbhy\HyperfAuth\AuthManager;

class AuthorizedAccessTokenController
{

    /**
     * The token repository implementation.
     *
     * @var TokenRepository
     */
    protected TokenRepository $tokenRepository;

    /**
     * The refresh token repository implementation.
     *
     * @var RefreshTokenRepository
     */
    protected RefreshTokenRepository $refreshTokenRepository;

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     *
     * @param TokenRepository $tokenRepository
     * @param RefreshTokenRepository $refreshTokenRepository
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, RefreshTokenRepository $refreshTokenRepository, AuthManager $auth)
    {
        $this->tokenRepository = $tokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all the authorized tokens for the authenticated user.
     *
     * @param Request $request
     * @return Collection
     */
    public function forUser(Request $request)
    {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return !$token->client->firstParty() && !$token->revoked;
        })->values();
    }

    /**
     * Delete the given token.
     *
     * @param Request $request
     * @param string $tokenId
     * @return Response
     */
    public function destroy(Request $request, $tokenId)
    {
        $user = $this->auth->guard('passport')->user();
        $token = $this->tokenRepository->findForUser(
            $tokenId, $user->getKey()
        );

        if (is_null($token)) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
        }

        $token->revoke();

        $this->refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

        $response = new Response();
        return $response->withStatus(204)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
    }

}
