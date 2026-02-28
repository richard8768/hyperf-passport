<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Qbhy\HyperfAuth\Exception\GuardException;
use Qbhy\HyperfAuth\Exception\UserProviderException;
use Richard\HyperfPassport\Exception\PassportException;
use stdClass;
use Throwable;

class PassportExceptionHandler extends ExceptionHandler
{
    #[Inject]
    protected RequestInterface $requests;

    public function handle(Throwable $throwable, ResponseInterface $response): MessageInterface|ResponseInterface
    {
        if ($throwable instanceof PassportException || $throwable instanceof GuardException || $throwable instanceof UserProviderException) {
            $this->stopPropagation();
            $handleData = $this->getHandleMsg();
            $emptyObj = new stdClass();
            if (empty($handleData)) {
                $data = ['status' => 999999, 'data' => $emptyObj, 'message' => $throwable->getMessage()];
            } else {
                $data = ['status' => $handleData['status'] ?? 999999, 'data' => $handleData['data'] ?? $emptyObj, 'message' => $throwable->getMessage()];
            }
            return $response->withHeader('Content-Type', 'application/json;charset=utf-8')->withStatus($throwable->getStatusCode())->withBody(new SwooleStream(json_encode($data)));
        }

        // 交给下一个异常处理器
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof PassportException;
    }

    protected function getHandleMsg(): array
    {
        return [];
    }
}
