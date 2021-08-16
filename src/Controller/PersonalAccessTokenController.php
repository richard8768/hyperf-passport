<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;
use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;
use Qbhy\HyperfAuth\AuthManager;

class PersonalAccessTokenController {

    /**
     * The token repository implementation.
     *
     * @var \Richard\HyperfPassport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * The validation factory implementation.
     *
     * @var \Hyperf\Validation\Contract\ValidatorFactoryInterface
     */
    protected $validation;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a controller instance.
     *
     * @param  \Richard\HyperfPassport\TokenRepository  $tokenRepository
     * @param  \Hyperf\Validation\Contract\ValidatorFactoryInterface  $validation
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation, AuthManager $auth) {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all of the personal access tokens for the authenticated user.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Hyperf\Database\Model\Collection
     */
    public function forUser(Request $request) {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
                    return $token->client->personal_access_client && !$token->revoked;
                })->values();
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \Richard\HyperfPassport\PersonalAccessTokenResult
     */
    public function store(Request $request) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'scopes' => 'array|in:' . implode(',', $passport->scopeIds()),
        ])->validate();
        $passportGuard = $this->auth->guard('passport');
        $provider = $passportGuard->getProvider()->getProviderName();
        $user = $passportGuard->user();
        return $user->createToken(
                        $request->input('name'), $request->input('scopes') ?: [], $provider
        );
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

        $response = new Response();
        return $response->withStatus(204)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
    }

}
