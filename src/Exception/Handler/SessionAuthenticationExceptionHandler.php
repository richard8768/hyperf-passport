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

namespace Richard\HyperfPassport\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use Richard\HyperfPassport\Exception\SessionAuthenticationException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class SessionAuthenticationExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @Inject
     * @var HttpResponse
     */
    protected HttpResponse $httpResponse;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
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
        if (
            $throwable instanceof SessionAuthenticationException
        ) {
            return true;
        }
        return false;
    }
}
