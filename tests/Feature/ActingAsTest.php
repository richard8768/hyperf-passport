<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Tests\Feature;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\RouteParser\Std;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Model\Model;
use Hyperf\HttpServer\Router\RouteCollector;
use Mockery;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Auth\AuthenticatableTrait;
use Richard\HyperfPassport\AuthManager;
use Richard\HyperfPassport\HasApiTokens;
use Richard\HyperfPassport\Middleware\CheckForAnyScope;
use Richard\HyperfPassport\Middleware\CheckScopes;
use Richard\HyperfPassport\Passport;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * @internal
 * @coversNothing
 */
class ActingAsTest extends PassportTestCase
{
    public function testActingAsWhenTheRouteIsProtectedByAuthMiddleware(): void
    {
        $parser = new Std();
        $generator = new DataGenerator();
        $collector = new RouteCollector($parser, $generator);

        $collector->get('/foo', function () {
            return 'bar';
        }, [
            'middleware' => ['auth:passport'],
        ]);

        self::actingAs(new PassportUser());

        go(function () {
             $this->get('/foo');
        });
        Assert::assertTrue(true);
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddleware(): void
    {
        $parser = new Std();
        $generator = new DataGenerator();
        $collector = new RouteCollector($parser, $generator);

        $collector->get('/foo', function () {
            return 'bar';
        }, [
            'middleware' => [CheckScopes::class . ':admin,footest'],
        ]);


        self::actingAs(new PassportUser(), ['admin', 'footest']);

        go(function () {
             $this->get('/foo');
        });
        Assert::assertTrue(true);
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware(): void
    {
        $parser = new Std();
        $generator = new DataGenerator();
        $collector = new RouteCollector($parser, $generator);

        $collector->get('/foo', function () {
            return 'bar';
        }, [
            'middleware' => [CheckForAnyScope::class . ':admin,footest'],
        ]);

        self::actingAs(new PassportUser(), ['footest']);

        go(function () {
             $this->get('/foo');
        });
        Assert::assertTrue(true);
    }

    /**
     * Set the current user for the application with the given scopes.
     *
     * @param Authenticatable|HasApiTokens $user
     * @param array $scopes
     * @param string $guard
     * @return Authenticatable
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function actingAs($user, array $scopes = [], string $guard = 'passport'): Authenticatable
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $token = Mockery::mock($passport->tokenModel())->shouldIgnoreMissing(false);

        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $user->withAccessToken($token);

        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('header')
            ->andReturn('');
        $request->shouldReceive('input')
            ->andReturn('');
        $authManager = ApplicationContext::getContainer()->get(AuthManager::class);
        $authManager->setServerRequest($request);
        $authManager->guard($guard)->login($user)->user();

        return $user;
    }
}

class PassportUser extends Model implements Authenticatable
{
    use HasApiTokens;
    use AuthenticatableTrait;
    use AuthAbility;

    protected ?string $table = 'users';
}
