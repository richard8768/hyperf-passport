<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Database\Model\Collection;
use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;
use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\PersonalAccessTokenResult;
use Richard\HyperfPassport\TokenRepository;
use Qbhy\HyperfAuth\AuthManager;

class PersonalAccessTokenController
{

    /**
     * The token repository implementation.
     *
     * @var TokenRepository
     */
    protected TokenRepository $tokenRepository;

    /**
     * The validation factory implementation.
     *
     * @var ValidationFactory
     */
    protected ValidationFactory $validation;

    /**
     * @var AuthManager
     */
    protected AuthManager $auth;

    /**
     * Create a controller instance.
     *
     * @param TokenRepository $tokenRepository
     * @param ValidationFactory $validation
     * @param AuthManager $auth
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation, AuthManager $auth)
    {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
        $this->auth = $auth;
    }

    /**
     * Get all the personal access tokens for the authenticated user.
     *
     * @param Request $request
     * @return Collection
     */
    public function forUser(Request $request)
    {
        $user = $this->auth->guard('passport')->user();
        $tokens = $this->tokenRepository->forUser($user->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return $token->client->personal_access_client && !$token->revoked;
        })->values();
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param Request $request
     * @return PersonalAccessTokenResult
     */
    public function store(Request $request)
    {
        $passport = make(Passport::class);
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

        $response = new Response();
        return $response->withStatus(204)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
    }

}
