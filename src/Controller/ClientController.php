<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Database\Model\Collection;
use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;
use Hyperf\HttpServer\Request;
use Hyperf\HttpMessage\Server\Response;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Rules\RedirectRule;
use Richard\HyperfPassport\Passport;
use Qbhy\HyperfAuth\AuthManager;

class ClientController {

    /**
     * The client repository instance.
     *
     * @var ClientRepository
     */
    protected $clients;

    /**
     * The validation factory implementation.
     *
     * @var ValidationFactory
     */
    protected $validation;

    /**
     * The redirect validation rule.
     *
     * @var RedirectRule
     */
    protected $redirectRule;

    /**
     * @var AuthManager
     */
    protected $auth;

    /**
     * Create a client controller instance.
     *
     * @param  ClientRepository  $clients
     * @param  ValidationFactory  $validation
     * @param  RedirectRule  $redirectRule
     * @return void
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
     *
     * @param  Request  $request
     * @return Collection
     */
    public function forUser(Request $request) {
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
     *
     * @param  Request  $request
     * @return Client|array
     */
    public function store(Request $request) {
        $passport = make(Passport::class);
        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'redirect' => ['required', $this->redirectRule],
            'confidential' => 'boolean',
        ])->validate();
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->create(
                $user->getKey(), $request->name, $request->redirect,
                null, false, false, (bool) $request->input('confidential', true)
        );

        if ($passport->hashesClientSecrets) {
            return ['plainSecret' => $client->plainSecret] + $client->toArray();
        }

        return $client->makeVisible('secret');
    }

    /**
     * Update the given client.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @return Response|Client
     */
    public function update(Request $request, $clientId) {
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->findForUser($clientId, $user->getKey());

        if (!$client) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:191',
            'redirect' => ['required', $this->redirectRule],
        ])->validate();

        return $this->clients->update(
                        $client, $request->name, $request->redirect
        );
    }

    /**
     * Delete the given client.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @return Response
     */
    public function destroy(Request $request, $clientId) {
        $user = $this->auth->guard('passport')->user();
        $client = $this->clients->findForUser($clientId, $user->getKey());

        if (!$client) {
            $response = new Response();
            return $response->withStatus(404)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
        }

        $this->clients->delete($client);

        $response = new Response();
        return $response->withStatus(204)->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(''));
    }

}
