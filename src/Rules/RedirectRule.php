<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Rules;

use Hyperf\Validation\Contract\ValidatorFactoryInterface as Factory;
use Hyperf\Validation\Contract\Rule;

class RedirectRule implements Rule
{

    /**
     * The validator instance.
     *
     * @var Factory
     */
    protected Factory $validator;

    /**
     * Create a new rule instance.
     *
     * @param Factory $validator
     * @return void
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function passes(string $attribute, $value): bool
    {
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
    public function message(): string
    {
        return 'One or more redirects have an invalid url format.';
    }

}
