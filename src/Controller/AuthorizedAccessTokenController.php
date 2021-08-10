<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\RefreshTokenRepository;
use Richard\HyperfPassport\TokenRepository;
use Qbhy\HyperfAuth\AuthManager;

class AuthorizedAccessTokenController {

    /**
     * The token repository implementation.
     *
     * @var \Richard\HyperfPassport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * The refresh token repository implementation.
     *
     * @var \Richard\HyperfPassport\RefreshTokenRepository
     */
    protected $refreshTokenRepository;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param  \Richard\HyperfPassport\TokenRepository  $tokenRepository
     * @param  \Richard\HyperfPassport\RefreshTokenRepository  $refreshTokenRepository
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, RefreshTokenRepository $refreshTokenRepository, AuthManager $auth) {
        $this->tokenRepository = $tokenRepository;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Hyperf\Database\Model\Collection
     */
    public function forUser(Request $request) {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
                    return !$token->client->firstParty() && !$token->revoked;
                })->values();
    }

    /**
     * Delete the given token.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @param  string  $tokenId
     * @return \Hyperf\HttpMessage\Server\Response
     */
    public function destroy(Request $request, $tokenId) {
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
