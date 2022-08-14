<?php

namespace Richard\HyperfPassport\Controller;

use Hyperf\Utils\Collection;
use Richard\HyperfPassport\Passport;

class ScopeController {

    /**
     * Get all the available scopes for the application.
     *
     * @return Collection
     */
    public function all() {
        $passport = make(Passport::class);
        return $passport->scopes();
    }

}
