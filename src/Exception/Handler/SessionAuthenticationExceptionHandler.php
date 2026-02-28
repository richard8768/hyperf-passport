<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Exception\Handler;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Richard\HyperfPassport\Exception\SessionAuthenticationException;
use Throwable;

class SessionAuthenticationExceptionHandler extends ExceptionHandler
{
    protected StdoutLoggerInterface $logger;

    #[Inject]
    protected HttpResponse $httpResponse;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());
        $redirectUrl = '/';

        switch (true) {
            case $throwable instanceof SessionAuthenticationException:
                $redirectUrl = $throwable->redirectTo() ?? '/';
                $response = $response->withStatus(403);
                break;
        }
        $this->stopPropagation();
        return $this->httpResponse->redirect($redirectUrl);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof SessionAuthenticationException;
    }
}
