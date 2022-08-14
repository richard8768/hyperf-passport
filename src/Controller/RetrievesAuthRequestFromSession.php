<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\HttpServer\Request;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Richard\HyperfPassport\Bridge\User;
use Richard\HyperfPassport\Exception\PassportException;

trait RetrievesAuthRequestFromSession {

    /**
     * Make sure the auth token matches the one in the session.
     *
     * @param  Request  $request
     * @return void
     *
     * @throws PassportException
     */
    protected function assertValidAuthToken(Request $request) {
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
     * @param  Request  $request
     * @return AuthorizationRequest
     *
     * @throws \Exception
     */
    protected function getAuthRequestFromSession(Request $request) {
        return tap($this->session->get('authRequest'), function ($authRequest) use ($request) {
            if (!$authRequest) {
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
