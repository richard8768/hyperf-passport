<?php

namespace Richard\HyperfPassport;

class TransientToken
{
    /**
     * Determine if the token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function can($scope): bool
    {
        return true;
    }

    /**
     * Determine if the token is missing a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function cant($scope): bool
    {
        return false;
    }

    /**
     * Determine if the token is a transient JWT token.
     *
     * @return bool
     */
    public function transient(): bool
    {
        return true;
    }
}
