<?php

namespace Richard\HyperfPassport\Controller;

use Exception;
use Hyperf\HttpServer\Request;
use Richard\HyperfPassport\Bridge\User;
use Richard\HyperfPassport\Exception\InvalidAuthTokenException;

trait RetrievesAuthRequestFromSession {

    /**
     * Make sure the auth token matches the one in the session.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return void
     *
     * @throws \Richard\HyperfPassport\Exception\InvalidAuthTokenException
     */
    protected function assertValidAuthToken(Request $request) {
        if ($request->has('auth_token') && $this->session->get('authToken') !== $request->get('auth_token')) {
            $this->session->forget(['authToken', 'authRequest']);

            throw InvalidAuthTokenException::different();
        }
    }

    /**
     * Get the authorization request from the session.
     *
     * @param  \Hyperf\HttpServer\Request  $request
     * @return \League\OAuth2\Server\RequestTypes\AuthorizationRequest
     *
     * @throws \Exception
     */
    protected function getAuthRequestFromSession(Request $request) {
        return tap($this->session->get('authRequest'), function ($authRequest) use ($request) {
            if (!$authRequest) {
                throw new Exception('Authorization request was not present in the session.');
            }
            $user = $this->auth->guard('passport')->user();
            $authRequest->setUser(new User($user->getKey()));

            $authRequest->setAuthorizationApproved(true);
        });
    }

}
