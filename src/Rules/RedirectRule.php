<?php

namespace Richard\HyperfPassport\Rules;

use Hyperf\Validation\Contract\ValidatorFactoryInterface as Factory;
use Hyperf\Validation\Contract\Rule;

class RedirectRule implements Rule {

    /**
     * The validator instance.
     *
     * @var \Hyperf\Validation\Contract\ValidatorFactoryInterface
     */
    protected $validator;

    /**
     * Create a new rule instance.
     *
     * @param  \Hyperf\Validation\Contract\ValidatorFactoryInterface  $validator
     * @return void
     */
    public function __construct(Factory $validator) {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, $value): bool {
        foreach (explode(',', $value) as $redirect) {
            $validator = $this->validator->make(['redirect' => $redirect], ['redirect' => 'url']);

            if ($validator->fails()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function message() {
        return 'One or more redirects have an invalid url format.';
    }

}
