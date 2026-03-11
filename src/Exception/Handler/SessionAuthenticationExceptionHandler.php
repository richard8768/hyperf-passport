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
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Stringable\Str;
use Psr\Http\Message\ResponseInterface;
use Richard\HyperfPassport\Exception\SessionAuthenticationException;
use Throwable;
use Hyperf\Context\RequestContext;
use function Hyperf\Support\value;

class SessionAuthenticationExceptionHandler extends ExceptionHandler
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        if ($throwable instanceof SessionAuthenticationException) {
            $this->stopPropagation();
            $redirectUrl = $throwable->redirectTo() ?? '/';
            $redirectUrl = value(function () use ($redirectUrl) {
                $dftSchema = 'http';
                if (Str::startsWith($redirectUrl, ['http://', 'https://'])) {
                    return $redirectUrl;
                }

                $host = RequestContext::get()->getUri()->getAuthority();

                return $dftSchema . '://' . $host . (Str::startsWith($redirectUrl, '/') ? $redirectUrl : '/' . $redirectUrl);
            });
            return $response->withStatus(302)->withAddedHeader('Location', $redirectUrl);
        }
        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof SessionAuthenticationException;
    }
}
