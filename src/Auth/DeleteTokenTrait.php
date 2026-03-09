<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Auth;

use Hyperf\HttpServer\Request;
use Richard\HyperfPassport\Exception\PassportException;
use Richard\HyperfPassport\Token;

trait DeleteTokenTrait
{
    public function getTokenId(Request $request): string
    {
        $tokenId = $request->route('token_id');
        if (empty($tokenId)) {
            throw new PassportException('token_id is required');
        }
        return $tokenId;
    }

    public function getTokenByTokenId($tokenId): ?Token
    {
        $user = $this->auth->guard('passport')->user();
        return $this->tokenRepository->findForUser(
            $tokenId,
            $user->getKey()
        );
    }

}
