<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Tests\Feature;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Commands\Migrations\FreshCommand;
use Hyperf\Testing\TestCase;
use Richard\HyperfPassport\Console\HashCommand;
use Richard\HyperfPassport\Console\KeysCommand;
use Richard\HyperfPassport\Guard\TokenGuard;
use Richard\HyperfPassport\Passport;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class PassportTestCase.
 * @method get($uri, $data = [], $headers = [])
 * @method post($uri, $data = [], $headers = [])
 * @method json($uri, $data = [], $headers = [])
 * @method file($uri, $data = [], $headers = [])
 * @method request($method, $path, $options = [])
 */
abstract class PassportTestCase extends TestCase
{
    public const KEYS = __DIR__ . '/../../storage';

    public const PUBLIC_KEY = self::KEYS . '/oauth-public.key';

    public const PRIVATE_KEY = self::KEYS . '/oauth-private.key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->runMigrateFresh();

        $passport = \Hyperf\Support\make(Passport::class);
        $passport->loadKeysFrom(self::KEYS);

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);

        $this->runPassportKeys();
    }

    protected function runMigrateFresh(): void
    {
        $runCommand = \Hyperf\Support\make(FreshCommand::class);
        $definition = new  InputDefinition([
            new InputArgument('command', null, 'command name', 'migrate:fresh'),
            new InputOption('database', null, InputOption::VALUE_REQUIRED, 'The database connection to use', 'default'),
            new InputOption('drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, 'The path to the migrations files to be executed'),
            new InputOption('realpath',
                null,
                InputOption::VALUE_NONE,
                'Indicate any provided migration file paths are pre-resolved absolute paths',
            ),
            new InputOption('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run'),
            new InputOption('seeder', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder'),
            new InputOption('step',
                null,
                InputOption::VALUE_NONE,
                'Force the migrations to be run so they can be rolled back individually',
            ),
        ]);
        $input = new ArrayInput([
            'command' => 'migrate:fresh',
        ], $definition);
        $runCommand->setInput($input);
        $runCommand->setOutput(new BufferedOutput());
        $application = $this->container->get(ApplicationInterface::class);
        $runCommand->setApplication($application);
        $runCommand->handle();
    }

    protected function runPassportKeys($isForce = false): string
    {
        $options = '';
        if ($isForce) {
            $options = ' --force';
        }
        $runCommand = \Hyperf\Support\make(KeysCommand::class);
        $definition = new  InputDefinition([
            new InputArgument('command', null, 'command name', 'passport:keys'),
            new InputOption('length', null, InputOption::VALUE_REQUIRED, 'The length of the private key', 4096),
            new InputOption('force', null, InputOption::VALUE_OPTIONAL, 'Overwrite keys they already exist', $options),
        ]);
        $input = new ArrayInput([
            'command' => 'passport:keys',
            '--length' => 4096,
        ], $definition);
        $runCommand->setInput($input);
        $application = $this->container->get(ApplicationInterface::class);
        $runCommand->setApplication($application);
        $runCommand->setOutput(new BufferedOutput());
        //$code = $runCommand->call('passport:keys',[]);
        $runCommand->handle();
        return $runCommand->getOutput()->fetch();
    }

    protected function runPassportHash(): void
    {
        $runCommand = \Hyperf\Support\make(HashCommand::class);
        $definition = new  InputDefinition([
            new InputArgument('command', null, 'command name', 'passport:keys'),
            new InputOption('length', null, InputOption::VALUE_REQUIRED, 'The length of the private key', 4096),
            new InputOption('force', null, InputOption::VALUE_OPTIONAL, 'Overwrite keys they already exist', true),
        ]);
        $input = new ArrayInput([
            'command' => 'passport:hash',
            '--force' => true,
        ], $definition);
        $runCommand->setInput($input);
        $application = $this->container->get(ApplicationInterface::class);
        $runCommand->setApplication($application);
        $runCommand->setOutput(new BufferedOutput());
        //$code = $runCommand->call('passport:hash', ['--force' => true]);
        $runCommand->handle();
    }

    protected function getEnvironmentSetUp(): void
    {
        $config = \Hyperf\Support\make(ConfigInterface::class);

        $config->set('auth.defaults.provider', 'users');

        if (($userClass = $this->getUserClass()) !== null) {
            $config->set('auth.providers.users.model', $userClass);
        }

        $config->set('auth.guards.passport', ['driver' => TokenGuard::class, 'provider' => 'users']);

        $config->set('passport.database_connection', 'testbench');

        $config->set('database.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get the Eloquent user model class name.
     *
     * @return null|string
     */
    protected function getUserClass()
    {
    }
}
