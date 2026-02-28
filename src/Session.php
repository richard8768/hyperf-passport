<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\Session\Session as HyperfSession;

class Session extends HyperfSession
{
    /**
     * Starts the session storage.
     */
    public function start(): bool
    {
        $this->loadSession();
        if (! $this->has('_token')) {
            $this->regenerateToken();
        }
        return $this->started = true;
    }
}
