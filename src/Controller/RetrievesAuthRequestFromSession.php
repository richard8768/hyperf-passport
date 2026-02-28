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
use Hyperf\HttpServer\Request;
use Hyperf\Tappable\HigherOrderTapProxy;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Richard\HyperfPassport\Bridge\User;
use Richard\HyperfPassport\Exception\PassportException;

trait RetrievesAuthRequestFromSession
{
    /**
     * Make sure the auth token matches the one in the session.
     *
     * @throws PassportException
     */
    protected function assertValidAuthToken(Request $request): void
    {
        if ($request->has('auth_token') && $this->session->get('authToken') !== $request->input('auth_token')) {
            $this->session->forget(['authToken', 'authRequest']);
            $exception = new PassportException('The provided auth token for the request is different from the session auth token.');
            $exception->setStatusCode(400);
            throw $exception;
        }
    }

    /**
     * Get the authorization request from the session.
     *
     * @throws Exception
     */
    protected function getAuthRequestFromSession(Request $request): AuthorizationRequest|HigherOrderTapProxy
    {
        return tap($this->session->get('authRequest'), function ($authRequest) {
            if (! $authRequest) {
                $exception = new PassportException('Authorization request was not present in the session.');
                $exception->setStatusCode(400);
                throw $exception;
            }
            $user = $this->auth->guard('session')->user();
            $authRequest->setUser(new User($user->getKey()));

            $authRequest->setAuthorizationApproved(true);
        });
    }
}
