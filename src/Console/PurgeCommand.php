<?php

namespace Richard\HyperfPassport\Console;

use Hyperf\Command\Command;
use Carbon\Carbon;
use Richard\HyperfPassport\Passport;

class PurgeCommand extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:purge
                            {--revoked : Only purge revoked tokens and authentication codes}
                            {--expired : Only purge expired tokens and authentication codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge revoked and / or expired tokens and authentication codes';

    /**
     * Execute the console command.
     */
    public function handle() {
        $expired = Carbon::now()->subDays(7);
        $passport = make(\Richard\HyperfPassport\Passport::class);
        if (($this->input->getOption('revoked') && $this->input->getOption('expired')) ||
                (!$this->input->getOption('revoked') && !$this->input->getOption('expired'))) {
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

    protected function configure() {
        $this->setDescription($this->description);
    }

}
