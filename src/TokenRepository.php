<?php

namespace Richard\HyperfPassport;

use Carbon\Carbon;

class TokenRepository {

    /**
     * Creates a new Access Token.
     *
     * @param  array  $attributes
     * @return \Richard\HyperfPassport\Token
     */
    public function create($attributes) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->token()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return \Richard\HyperfPassport\Token
     */
    public function find($id) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->token()->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Richard\HyperfPassport\Token|null
     */
    public function findForUser($id, $userId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->token()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Hyperf\Database\Model\Collection
     */
    public function forUser($userId) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->token()->where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param  \Hyperf\DbConnection\Model\Model  $user
     * @param  \Richard\HyperfPassport\Client  $client
     * @return \Richard\HyperfPassport\Token|null
     */
    public function getValidToken($user, $client) {
        return $client->tokens()
                        ->whereUserId($user->getKey())
                        ->where('revoked', 0)
                        ->where('expires_at', '>', Carbon::now())
                        ->first();
    }

    /**
     * Store the given token instance.
     *
     * @param  \Richard\HyperfPassport\Token  $token
     * @return void
     */
    public function save(Token $token) {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id) {
        $passport = make(\Richard\HyperfPassport\Passport::class);
        return $passport->token()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param  string  $id
     * @return bool
     */
    public function isAccessTokenRevoked($id) {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @param  \Hyperf\DbConnection\Model\Model  $user
     * @param  \Richard\HyperfPassport\Client  $client
     * @return \Richard\HyperfPassport\Token|null
     */
    public function findValidToken($user, $client) {
        return $client->tokens()
                        ->whereUserId($user->getKey())
                        ->where('revoked', 0)
                        ->where('expires_at', '>', Carbon::now())
                        ->latest('expires_at')
                        ->first();
    }

}
