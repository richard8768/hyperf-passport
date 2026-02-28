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

use Carbon\Carbon;
use Hyperf\Command\Command;
use Richard\HyperfPassport\Passport;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'passport:purge
                            {--revoked : Only purge revoked tokens and authentication codes}
                            {--expired : Only purge expired tokens and authentication codes}';

    /**
     * The console command description.
     */
    protected string $description = 'Purge revoked and / or expired tokens and authentication codes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $expired = Carbon::now()->subDays(7);
        $passport = make(Passport::class);
        if (($this->input->getOption('revoked') && $this->input->getOption('expired'))
            || (! $this->input->getOption('revoked') && ! $this->input->getOption('expired'))) {
            $passport->token()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            $passport->authCode()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            $passport->refreshToken()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged revoked items and items expired for more than seven days.');
        } elseif ($this->input->getOption('revoked')) {
            $passport->token()->where('revoked', 1)->delete();
            $passport->authCode()->where('revoked', 1)->delete();
            $passport->refreshToken()->where('revoked', 1)->delete();

            $this->info('Purged revoked items.');
        } elseif ($this->input->getOption('expired')) {
            $passport->token()->whereDate('expires_at', '<', $expired)->delete();
            $passport->authCode()->whereDate('expires_at', '<', $expired)->delete();
            $passport->refreshToken()->whereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged items expired for more than seven days.');
        }
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }
}
