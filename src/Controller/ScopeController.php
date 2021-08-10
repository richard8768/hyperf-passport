<?php

namespace Richard\HyperfPassport\Controller;

use Richard\HyperfPassport\Passport;

class ScopeController {

    /**
     * Get all of the available scopes for the application.
     *
     * @return \Hyperf\Utils\Collection
     */
    public function all() {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->scopes();
    }

}
