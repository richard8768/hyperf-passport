<?php

namespace Richard\HyperfPassport;

use Hyperf\Utils\Contracts\Arrayable;
use Richard\HyperfPassport\Contracts\Jsonable;

class PersonalAccessTokenResult implements Arrayable, Jsonable {

    /**
     * The access token.
     *
     * @var string
     */
    public $accessToken;

    /**
     * The token model instance.
     *
     * @var Token
     */
    public $token;

    /**
     * Create a new result instance.
     *
     * @param  string  $accessToken
     * @param Token $token
     * @return void
     */
    public function __construct($accessToken, $token) {
        $this->token = $token;
        $this->accessToken = $accessToken;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'accessToken' => $this->accessToken,
            'token' => $this->token,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0) {
        return json_encode($this->toArray(), $options);
    }

}
