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
use Hyperf\Contract\SessionInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Request;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Qbhy\HyperfAuth\AuthManager;

class ApproveAuthorizationController
{
    use ConvertsPsrResponses;
    use RetrievesAuthRequestFromSession;

    /**
     * The authorization server.
     */
    protected AuthorizationServer $server;

    protected SessionInterface $session;

    protected AuthManager $auth;

    /**
     * Create a new controller instance.
     */
    public function __construct(AuthorizationServer $server, SessionInterface $session, AuthManager $auth)
    {
        $this->server = $server;
        $this->session = $session;
        $this->auth = $auth;
    }

    /**
     * Approve the authorization request.
     *
     * @throws Exception
     */
    public function approve(Request $request): Response
    {
        $this->assertValidAuthToken($request);

        $authRequest = $this->getAuthRequestFromSession($request);

        return $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response())
        );
    }
}
