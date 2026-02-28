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
use Richard\HyperfPassport\Passport;

class HashCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'passport:hash {--force : Force the operation to run without confirmation prompt}';

    /**
     * The console command description.
     */
    protected string $description = 'Hash all of the existing secrets in the clients table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $passport = make(Passport::class);
        if (! $passport->hashesClientSecrets) {
            $this->warn('Please enable client hashing yet in your AppServiceProvider before continuing.');

            return;
        }

        if ($this->input->getOption('force') || $this->confirm('Are you sure you want to hash all client secrets? This cannot be undone.')) {
            $model = $passport->clientModel();

            foreach ((new $model())->whereNotNull('secret')->cursor() as $client) {
                if (password_get_info($client->secret)['algo'] === PASSWORD_BCRYPT) {
                    continue;
                }

                $client->timestamps = false;

                $client->forceFill([
                    'secret' => $client->secret,
                ])->save();
            }

            $this->info('All client secrets were successfully hashed.');
        }
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }
}
