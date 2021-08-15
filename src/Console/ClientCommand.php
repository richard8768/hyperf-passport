<?php

namespace Richard\HyperfPassport\Console;

use Hyperf\Command\Command;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;

class ClientCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client
            {--personal : Create a personal access token client}
            {--password : Create a password grant client}
            {--client : Create a client credentials grant client}
            {--name= : The name of the client}
            {--provider= : The name of the user provider}
            {--redirect_uri= : The URI to redirect to after authorization }
            {--user_id= : The user ID the client should be assigned to }
            {--public : Create a public client (Auth code grant type only) }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client for issuing access tokens';

    /**
     * Execute the console command.
     *
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @return void
     */
    public function handle() {
        $clients = make(\Richard\HyperfPassport\ClientRepository::class);
        if ($this->input->getOption('personal')) {
            $this->createPersonalClient($clients);
        } elseif ($this->input->getOption('password')) {
            $this->createPasswordClient($clients);
        } elseif ($this->input->getOption('client')) {
            $this->createClientCredentialsClient($clients);
        } else {
            $this->createAuthCodeClient($clients);
        }
    }

    /**
     * Create a new personal access client.
     *
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @return void
     */
    protected function createPersonalClient(ClientRepository $clients) {
        $name = $this->input->getOption('name') ?: $this->ask(
                        'What should we name the personal access client?',
                        config('APP_NAME') . ' Personal Access Client'
        );

        $providers = array_keys(config('auth.providers'));

        $provider = $this->input->getOption('provider') ?: $this->choice(
                        'Which user provider should this client use to retrieve users?',
                        $providers,
                        in_array('users', $providers) ? 'users' : null
        );

        $client = $clients->createPersonalAccessClient(
                null, $name, 'http://localhost', $provider
        );

        $this->info('Personal access client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a new password grant client.
     *
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @return void
     */
    protected function createPasswordClient(ClientRepository $clients) {
        $name = $this->input->getOption('name') ?: $this->ask(
                        'What should we name the password grant client?',
                        config('APP_NAME') . ' Password Grant Client'
        );

        $providers = array_keys(config('auth.providers'));

        $provider = $this->input->getOption('provider') ?: $this->choice(
                        'Which user provider should this client use to retrieve users?',
                        $providers,
                        in_array('users', $providers) ? 'users' : null
        );

        $client = $clients->createPasswordGrantClient(
                null, $name, 'http://localhost', $provider
        );

        $this->info('Password grant client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a client credentials grant client.
     *
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @return void
     */
    protected function createClientCredentialsClient(ClientRepository $clients) {
        $name = $this->input->getOption('name') ?: $this->ask(
                        'What should we name the client?',
                        config('APP_NAME') . ' ClientCredentials Grant Client'
        );

        $providers = array_keys(config('auth.providers'));

        $provider = $this->input->getOption('provider') ?: $this->choice(
                        'Which user provider should this client use to retrieve users?',
                        $providers,
                        in_array('users', $providers) ? 'users' : null
        );

        $client = $clients->create(
                null, $name, '', $provider
        );

        $this->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a authorization code client.
     *
     * @param  \Richard\HyperfPassport\ClientRepository  $clients
     * @return void
     */
    protected function createAuthCodeClient(ClientRepository $clients) {
        $userId = $this->input->getOption('user_id') ?: $this->ask(
                        'Which user ID should the client be assigned to?'
        );

        $name = $this->input->getOption('name') ?: $this->ask(
                        'What should we name the client?'
        );

        $redirect = $this->input->getOption('redirect_uri') ?: $this->ask(
                        'Where should we redirect the request after authorization?',
                        $this->genUrl('/auth/callback')
        );

        $providers = array_keys(config('auth.providers'));

        $provider = $this->input->getOption('provider') ?: $this->choice(
                        'Which user provider should this client use to retrieve users?',
                        $providers,
                        in_array('users', $providers) ? 'users' : null
        );

        $client = $clients->create(
                $userId, $name, $redirect, $provider, false, false, !$this->input->getOption('public')
        );

        $this->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Output the client's ID and secret key.
     *
     * @param  \Richard\HyperfPassport\Client  $client
     * @return void
     */
    protected function outputClientDetails(Client $client) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if ($passport->hashesClientSecrets) {
            $this->line('<comment>Here is your new client secret. This is the only time it will be shown so don\'t lose it!</comment>');
            $this->line('');
        }

        $this->line('<comment>Client ID:</comment> ' . $client->id);
        $this->line('<comment>Client secret:</comment> ' . $client->plainSecret);
    }

    protected function configure() {
        $this->setDescription($this->description);
    }

	protected function genUrl(string $toUrl) {
            if (! \Hyperf\Utils\ApplicationContext::hasContainer() || \Hyperf\Utils\Str::startsWith($toUrl, ['http://', 'https://'])) {
                return $toUrl;
            }
            return 'http://localhost' . (\Hyperf\Utils\Str::startsWith($toUrl, '/') ? $toUrl : '/' . $toUrl);
        }

}
