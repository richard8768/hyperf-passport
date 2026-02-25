<?php

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
