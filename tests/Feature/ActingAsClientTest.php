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
use Hyperf\HttpServer\Router\RouteCollector;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ServerRequestInterface;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\Middleware\CheckClientCredentials;
use Richard\HyperfPassport\Middleware\CheckClientCredentialsForAnyScope;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\TokenRepository;

/**
 * @internal
 * @coversNothing
 */
class ActingAsClientTest extends PassportTestCase
{
    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsMiddleware(): void
    {
        $parser = new Std();
        $generator = new DataGenerator();
        $collector = new RouteCollector($parser, $generator);

        $collector->get('/foo', function () {
            return 'bar';
        }, [
            'middleware' => [CheckClientCredentials::class],
        ]);

        self::actingAsClient(new Client());

        go(function () {
             $this->get('/foo');
        });
        Assert::assertTrue(true);
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsForAnyScope(): void
    {
        $parser = new Std();
        $generator = new DataGenerator();
        $collector = new RouteCollector($parser, $generator);

        $collector->get('/foo', function () {
            return 'bar';
        }, [
            'middleware' => [CheckClientCredentialsForAnyScope::class . ':testFoo'],
        ]);

        self::actingAsClient(new Client(), ['testFoo']);

        go(function () {
            $this->get('/foo');
        });
        Assert::assertTrue(true);
    }

    /**
     * Set the current client for the application with the given scopes.
     *
     * @param Client $client
     * @param array $scopes
     * @return Client
     */
    public static function actingAsClient($client, $scopes = []): Client
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $token = \Hyperf\Support\make($passport->tokenModel());

        $token->client_id = $client->id;
        $token->setRelation('client', $client);

        $token->scopes = $scopes;

        $mock = Mockery::mock(ResourceServer::class);
        $mock->shouldReceive('validateAuthenticatedRequest')
            ->andReturnUsing(function (ServerRequestInterface $request) use ($token) {
                return $request->withAttribute('oauth_client_id', $token->client->id)
                    ->withAttribute('oauth_access_token_id', $token->id)
                    ->withAttribute('oauth_scopes', $token->scopes);
            });

        ApplicationContext::getContainer()->set(ResourceServer::class, $mock);

        $mock = Mockery::mock(TokenRepository::class);
        $mock->shouldReceive('find')->andReturn($token);

        ApplicationContext::getContainer()->set(TokenRepository::class, $mock);

        return $client;
    }
}
