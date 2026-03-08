<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Console;

use Hyperf\Command\Command;
use Hyperf\Context\ApplicationContext;
use Hyperf\Stringable\Str;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\Passport;

class ClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'passport:client
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
     */
    protected string $description = 'Create a client for issuing access tokens';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $clients = \Hyperf\Support\make(ClientRepository::class);
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
     */
    protected function createPersonalClient(ClientRepository $clients): void
    {
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
            0,
            $name,
            'http://localhost',
            $provider
        );

        $this->info('Personal access client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a new password grant client.
     */
    protected function createPasswordClient(ClientRepository $clients): void
    {
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
            0,
            $name,
            'http://localhost',
            $provider
        );

        $this->info('Password grant client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a client credentials grant client.
     */
    protected function createClientCredentialsClient(ClientRepository $clients): void
    {
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
            0,
            $name,
            '',
            $provider
        );

        $this->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a authorization code client.
     */
    protected function createAuthCodeClient(ClientRepository $clients): void
    {
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
            (int)$userId,
            $name,
            $redirect,
            $provider,
            false,
            false,
            ! $this->input->getOption('public')
        );

        $this->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Output the client's ID and secret key.
     */
    protected function outputClientDetails(Client $client): void
    {
        $passport = \Hyperf\Support\make(Passport::class);
        if ($passport->hashesClientSecrets) {
            $this->line('<comment>Here is your new client secret. This is the only time it will be shown so don\'t lose it!</comment>');
            $this->line('');
        }

        $this->line('<comment>Client ID:</comment> ' . $client->id);
        $this->line('<comment>Client secret:</comment> ' . $client->plainSecret);
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }

    protected function genUrl(string $toUrl): string
    {
        if (! ApplicationContext::hasContainer() || Str::startsWith($toUrl, ['http://', 'https://'])) {
            return $toUrl;
        }
        return 'http://localhost' . (Str::startsWith($toUrl, '/') ? $toUrl : '/' . $toUrl);
    }
}
