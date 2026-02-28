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
use Hyperf\Collection\Arr;
use phpseclib\Crypt\RSA as LegacyRSA;
use phpseclib3\Crypt\RSA;
use Richard\HyperfPassport\Passport;

class KeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'passport:keys
                                      {--force : Overwrite keys they already exist}
                                      {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     */
    protected string $description = 'Create the encryption keys for API authentication';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $passport = make(Passport::class);
        [$publicKey, $privateKey] = [
            $passport->keyPath('oauth-public.key'),
            $passport->keyPath('oauth-private.key'),
        ];

        if ((file_exists($publicKey) || file_exists($privateKey)) && ! $this->input->getOption('force')) {
            $this->error('Encryption keys already exist. Use the --force option to overwrite them.');
        } else {
            if (class_exists(LegacyRSA::class)) {
                $keys = (new LegacyRSA())->createKey($this->input ? (int) $this->input->getOption('length') : 4096);

                file_put_contents($publicKey, Arr::get($keys, 'publickey'));
                file_put_contents($privateKey, Arr::get($keys, 'privatekey'));
            } else {
                $key = RSA::createKey($this->input ? (int) $this->input->getOption('length') : 4096);

                file_put_contents($publicKey, (string) $key->getPublicKey());
                file_put_contents($privateKey, (string) $key);
            }

            $this->info('Encryption keys generated successfully.');
        }
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }
}
