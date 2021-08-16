<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Richard\HyperfPassport;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Richard\HyperfPassport\Exception\PassportException;
use Throwable;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\Exception\GuardException;
use Qbhy\HyperfAuth\Exception\UserProviderException;

class PassportExceptionHandler extends ExceptionHandler {

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $requests;

    public function handle(Throwable $throwable, ResponseInterface $response) {
        if (($throwable instanceof PassportException) || ($throwable instanceof GuardException) || ($throwable instanceof UserProviderException)) {
            $this->stopPropagation();
            $handleData = $this->getHandleMsg();
            if (empty($handleData)) {
                $emptyObj = new \stdClass();
                $data = ['status' => 999999, 'data' => $emptyObj, 'message' => $throwable->getMessage()];
            } else {
                $data = ['status' => $handleData['status'] ?? 999999, 'data' => $handleData['data'] ?? $emptyObj, 'message' => $throwable->getMessage()];
            }
            return $response->withHeader('Content-Type', 'application/json;charset=utf-8')
                            ->withStatus($throwable->getStatusCode())->withBody(new SwooleStream(json_encode($data)));
        }

        // 交给下一个异常处理器
        return $response;
    }

    public function isValid(Throwable $throwable): bool {
        return $throwable instanceof PassportException;
    }

    protected function getHandleMsg(): array {
        return [];
    }

}
