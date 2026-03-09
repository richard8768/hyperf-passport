<?php

declare(strict_types=1);

/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Request;

!defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

$container = new Container((new DefinitionSourceFactory(false))());
ApplicationContext::setContainer($container);

$container->define(RequestInterface::class, function () {
    return new Request();
});

