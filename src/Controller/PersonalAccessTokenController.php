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
use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;
use Qbhy\HyperfAuth\AuthManager;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\PersonalAccessTokenResult;
use Richard\HyperfPassport\TokenRepository;
use Richard\HyperfPassport\Auth\DeleteTokenTrait;

class PersonalAccessTokenController
{
    use DeleteTokenTrait;
    /**
     * The token repository implementation.
     */
    protected TokenRepository $tokenRepository;

    /**
     * The validation factory implementation.
     */
    protected ValidationFactory $validation;

    protected AuthManager $auth;

    /**
     * Create a controller instance.
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation, AuthManager $auth)
    {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all the personal access tokens for the authenticated user.
     */
    public function forUser(Request $request): Collection|\Hyperf\Collection\Collection
    {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return $token->client->personal_access_client && !$token->revoked;
        })->values();
    }

    /**
     * Create a new personal access token for the user.
     */
    public function store(Request $request): PersonalAccessTokenResult
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'provider' => 'string|max:20',
            'scopes' => 'array|in:' . implode(',', $passport->scopeIds()),
        ])->validate();
        $passportGuard = $this->auth->guard('passport');
        $reqProvider = $request->input('provider');
        $provider = (!empty($reqProvider)) ? $reqProvider : $passportGuard->getProvider()->getProviderName();
        return $passportGuard->user()->createToken(
            $request->input('name'),
            $request->input('scopes') ?: [],
            $provider
        );
    }

    /**
     * Delete the given token.
     */
    public function destroy(Request $request): Response
    {
        $tokenId = $this->getTokenId($request);
        $token = $this->getTokenByTokenId($tokenId);

        if (is_null($token)) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new SwooleStream(''));
        }

        $token->revoke();

        $response = new Response();
        return $response->withStatus(204)->withBody(new SwooleStream(''));
    }
}
