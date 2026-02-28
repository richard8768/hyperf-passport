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

use Exception;
use Hyperf\Collection\Arr;
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Response;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\AuthManager;

class DenyAuthorizationController
{
    use RetrievesAuthRequestFromSession;

    /**
     * The response factory implementation.
     */
    protected Response $response;

    protected SessionInterface $session;

    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     */
    public function __construct(Response $response, SessionInterface $session, AuthManager $auth)
    {
        $this->response = $response;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Deny the authorization request.
     *
     * @throws Exception
     */
    public function deny(Request $request): ResponseInterface
    {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);

        $clientUris = Arr::wrap($authRequest->getClient()->getRedirectUri());

        if (! in_array($uri = $authRequest->getRedirectUri(), $clientUris)) {
            $uri = Arr::first($clientUris);
        }

        $separator = ($authRequest->getGrantTypeId() === 'implicit') ? '#' : (str_contains($uri, '?') ? '&' : '?');

        return $this->response->redirect(
            $uri . $separator . 'error=access_denied&state=' . $request->input('state')
        );
    }
}
