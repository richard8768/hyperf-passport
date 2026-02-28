<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Exception;

use Hyperf\Server\Exception\ServerException;

class SessionAuthenticationException extends ServerException
{
    /**
     * All the guards that were checked.
     */
    protected array $guards;

    /**
     * The path the user should be redirected to.
     */
    protected ?string $redirectTo;

    /**
     * Create a new authentication exception.
     *
     * @param null|string $redirectTo
     */
    public function __construct(string $message = 'Unauthenticated.', array $guards = [], $redirectTo = null)
    {
        parent::__construct($message);

        $this->guards = $guards;
        $this->redirectTo = $redirectTo;
    }

    /**
     * Get the guards that were checked.
     */
    public function guards(): array
    {
        return $this->guards;
    }

    /**
     * Get the path the user should be redirected to.
     */
    public function redirectTo(): ?string
    {
        return $this->redirectTo;
    }
}
