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
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Richard\HyperfPassport\Passport;

class InstallCommand extends Command
{
    #[Inject]
    protected ConfigInterface $config;

    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'passport:install
                            {--uuids : Use UUIDs for all client IDs}
                            {--force : Overwrite keys they already exist}
                            {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     */
    protected string $description = 'Run the commands necessary to prepare Passport for use';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $provider = in_array('users', array_keys(config('auth.providers'))) ? 'users' : null;

        $this->call('passport:keys', ['--force' => $this->input->getOption('force'), '--length' => $this->input->getOption('length')]);

        if ($this->input->getOption('uuids')) {
            $this->configureUuids();
        }

        $this->call('passport:client', ['--personal' => true, '--name' => config('APP_NAME') . ' Personal Access Client', '--provider' => $provider]);
        $this->call('passport:client', ['--password' => true, '--name' => config('APP_NAME') . ' Password Grant Client', '--provider' => $provider]);
        //$this->call('passport:client', ['--client' => true, '--name' => config('APP_NAME') . ' Client Credentials Grant Client', '--provider' => $provider]);
        $this->call('passport:client', ['--user_id' => 999999, '--redirect_uri' => 'http://localhost/auth/callback', '--name' => config('APP_NAME') . ' Auth Code Grant Client', '--provider' => $provider]);
    }

    /**
     * Configure Passport for client UUIDs.
     */
    protected function configureUuids(): void
    {
        $this->call('vendor:publish', ['package' => 'richard/hyperf-passport', '--id' => 'config', '--force']);
        $this->call('vendor:publish', ['package' => 'richard/hyperf-passport', '--id' => 'migrations', '--force']);
        $passport = \Hyperf\Support\make(Passport::class);
        $this->config->set('passport.client_uuids', true);
        $passport->setClientUuids(true);

        $this->replaceInFile(BASE_PATH . '/config/autoload/passport.php', '\'client_uuids\' => false', '\'client_uuids\' => true');
        $this->replaceInFile(BASE_PATH . '/migrations/2016_06_01_000001_create_oauth_auth_codes_table.php', '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
        $this->replaceInFile(BASE_PATH . '/migrations/2016_06_01_000002_create_oauth_access_tokens_table.php', '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
        $this->replaceInFile(BASE_PATH . '/migrations/2016_06_01_000004_create_oauth_clients_table.php', '$table->bigIncrements(\'id\');', '$table->uuid(\'id\')->primary();');
        $this->replaceInFile(BASE_PATH . '/migrations/2016_06_01_000005_create_oauth_personal_access_clients_table.php', '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');

        if ($this->confirm('In order to finish configuring client UUIDs, we need to rebuild the Passport database tables. Would you like to rollback and re-run your last migration?')) {
            $this->call('migrate:rollback');
            $this->call('migrate');
            $this->line('');
        }
    }

    /**
     * Replace a given string in a given file.
     *
     * @param string $path
     * @param string $search
     * @param string $replace
     */
    protected function replaceInFile($path, $search, $replace): void
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }
}
