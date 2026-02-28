<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\HttpServer\Router\DispatcherFactory as Dispatcher;
use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\HttpServer\Router\Router;

class DispatcherFactory extends Dispatcher
{
    public function initConfigRoute(): void
    {
        parent::initConfigRoute();
        $this->all();
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     */
    public function all(): void
    {
        $this->forAuthorization();
        $this->forAccessTokens();
        $this->forTransientTokens();
        $this->forClients();
        $this->forPersonalAccessTokens();
    }

    /**
     * Register the routes needed for authorization.
     */
    public function forAuthorization(): void
    {
        Router::addGroup('/oauth', function (RouteCollector $router) {// need web auth middleware
            $router->addRoute('GET', '/authorize', '\Richard\HyperfPassport\Controller\AuthorizationController@authorize');
            $router->addRoute('POST', '/authorize', '\Richard\HyperfPassport\Controller\ApproveAuthorizationController@approve');
            $router->addRoute('DELETE', '/authorize', '\Richard\HyperfPassport\Controller\DenyAuthorizationController@deny');
        }, ['middleware' => [SessionAuthMiddleware::class]]);
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     */
    public function forAccessTokens(): void
    {
        Router::addRoute(['POST'], '/oauth/token', '\Richard\HyperfPassport\Controller\AccessTokenController@issueToken');
        Router::addGroup('/oauth', function (RouteCollector $router) {
            $router->addRoute('GET', '/tokens', '\Richard\HyperfPassport\Controller\AuthorizedAccessTokenController@forUser');
            $router->addRoute('DELETE', '/tokens/{token_id}', '\Richard\HyperfPassport\Controller\AuthorizedAccessTokenController@destroy');
        }, ['middleware' => [PassportAuthMiddleware::class]]);
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     */
    public function forTransientTokens(): void
    {
        Router::addRoute(['POST'], '/oauth/token/refresh', '\Richard\HyperfPassport\Controller\TransientTokenController@refresh');
    }

    /**
     * Register the routes needed for managing clients.
     */
    public function forClients(): void
    {
        Router::addGroup('/oauth', function (RouteCollector $router) {
            $router->addRoute('GET', '/clients', '\Richard\HyperfPassport\Controller\ClientController@forUser');
            $router->addRoute('POST', '/clients', '\Richard\HyperfPassport\Controller\ClientController@store');
            $router->addRoute('PUT', '/clients/{client_id}', '\Richard\HyperfPassport\Controller\ClientController@update');
            $router->addRoute('DELETE', '/clients/{client_id}', '\Richard\HyperfPassport\Controller\ClientController@destroy');
        }, ['middleware' => [PassportAuthMiddleware::class]]);
    }

    /**
     * Register the routes needed for managing personal access tokens.
     */
    public function forPersonalAccessTokens(): void
    {
        Router::addGroup('/oauth', function (RouteCollector $router) {
            $router->addRoute(
                'GET',
                '/scopes',
                '\Richard\HyperfPassport\Controller\ScopeController@all',
                ['middleware' => [PassportAuthMiddleware::class]]
            );
            $router->addRoute(
                'GET',
                '/personal-access-tokens',
                '\Richard\HyperfPassport\Controller\PersonalAccessTokenController@forUser',
                ['middleware' => [PassportAuthMiddleware::class]]
            );
            $router->addRoute(
                'POST',
                '/personal-access-tokens',
                '\Richard\HyperfPassport\Controller\PersonalAccessTokenController@store',
                ['middleware' => [PassportAuthMiddleware::class]]
            );
            $router->addRoute(
                'DELETE',
                '/personal-access-tokens/{token_id}',
                '\Richard\HyperfPassport\Controller\PersonalAccessTokenController@destroy',
                ['middleware' => [PassportAuthMiddleware::class]]
            );
        });
    }
}
