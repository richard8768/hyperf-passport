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
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\Rules\RedirectRule;

class ClientController
{
    /**
     * The client repository instance.
     */
    protected ClientRepository $clients;

    /**
     * The validation factory implementation.
     */
    protected ValidationFactory $validation;

    /**
     * The redirect validation rule.
     */
    protected RedirectRule $redirectRule;

    protected AuthManager $auth;

    /**
     * Create a client controller instance.
     */
    public function __construct(
        ClientRepository $clients,
        ValidationFactory $validation,
        RedirectRule $redirectRule,
        AuthManager $auth
    ) {
        $this->clients = $clients;
        $this->validation = $validation;
        $this->redirectRule = $redirectRule;
        $this->auth = $auth;
    }

    /**
     * Get all the clients for the authenticated user.
     */
    public function forUser(Request $request): Collection
    {
        $passport = make(Passport::class);
        $user = $this->auth->guard('passport')->user();
        $userId = $user->getKey();

        $clients = $this->clients->activeForUser($userId);

        if ($passport->hashesClientSecrets) {
            return $clients;
        }

        return $clients->makeVisible('secret');
    }

    /**
     * Store a new client.
     */
    public function store(Request $request): array|Client
    {
        $passport = make(Passport::class);
        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'redirect' => ['required', $this->redirectRule],
            'confidential' => 'boolean',
        ])->validate();
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->create(
            $user->getKey(),
            $request->name,
            $request->redirect,
            null,
            false,
            false,
            (bool) $request->input('confidential', true)
        );

        if ($passport->hashesClientSecrets) {
            return ['plainSecret' => $client->plainSecret] + $client->toArray();
        }

        return $client->makeVisible('secret');
    }

    /**
     * Update the given client.
     *
     * @param string $clientId
     */
    public function update(Request $request, $clientId): Client|Response
    {
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->findForUser($clientId, $user->getKey());

        if (! $client) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new SwooleStream(''));
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'redirect' => ['required', $this->redirectRule],
        ])->validate();

        return $this->clients->update(
            $client,
            $request->name,
            $request->redirect
        );
    }

    /**
     * Delete the given client.
     *
     * @param string $clientId
     */
    public function destroy(Request $request, $clientId): Response
    {
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->findForUser($clientId, $user->getKey());

        if (! $client) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new SwooleStream(''));
        }

        $this->clients->delete($client);

        $response = new Response();
        return $response->withStatus(204)->withBody(new SwooleStream(''));
    }
}
