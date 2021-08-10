<?php

declare(strict_types=1);

namespace Richard\HyperfPassport;

use Hyperf\Session\Session as HyperfSession;

class Session extends HyperfSession {

    /**
     * Starts the session storage.
     */
    public function start(): bool {
        $this->loadSession();
        if (!$this->has('_token')) {
            $this->regenerateToken();
        }
        return $this->started = true;
    }

}
