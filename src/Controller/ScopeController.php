<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Controller;

use Hyperf\Collection\Collection;
use Richard\HyperfPassport\Passport;

class ScopeController
{

    /**
     * Get all the available scopes for the application.
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return make(Passport::class)->scopes();
    }

}
