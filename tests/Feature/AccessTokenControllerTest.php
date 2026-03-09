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

use Carbon\CarbonImmutable;
use Hyperf\Database\Model\Factory;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Model\Model;
use HyperfExt\Hashing\HashManager;
use Lcobucci\JWT\Configuration;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use PHPUnit\Framework\Assert;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Auth\AuthenticatableTrait;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\ClientRepository;
use Richard\HyperfPassport\HasApiTokens;
use Richard\HyperfPassport\Passport;
use Hyperf\Stringable\Str;
use Richard\HyperfPassport\Token;
use Richard\HyperfPassport\TokenRepository;
use Faker\Factory as Faker;

/**
 * @internal
 * @coversNothing
 */
class AccessTokenControllerTest extends PassportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function testGettingAccessTokenWithClientCredentialsGrant(): void
    {
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make('foobar123');
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => false,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                ]
            );

            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayHasKey('token_type', $decodedResponse);
            $this->assertArrayHasKey('expires_in', $decodedResponse);
            $this->assertArrayHasKey('access_token', $decodedResponse);
            $this->assertSame('Bearer', $decodedResponse['token_type']);
            $expiresInSeconds = 31536000;
            $this->assertEqualsWithDelta($expiresInSeconds, $decodedResponse['expires_in'], 5);

            $jwtAccessToken = \Hyperf\Support\make(Configuration::class)->withValidationConstraints()->parser()->parse($decodedResponse['access_token']);
            $this->assertTrue(\Hyperf\Support\make(ClientRepository::class)->findActive($jwtAccessToken->claims()->get('aud'))->is($client));

            $token = \Hyperf\Support\make(TokenRepository::class)->find($jwtAccessToken->claims()->get('jti'));
            $this->assertInstanceOf(Token::class, $token);
            $this->assertTrue($token->client->is($client));
            $this->assertFalse($token->revoked);
            $this->assertNull($token->name);
            $this->assertNull($token->user_id);
            $this->assertLessThanOrEqual(5, CarbonImmutable::now()->addSeconds($expiresInSeconds)->diffInSeconds($token->expires_at));
        });
        Assert::assertTrue(true);
    }

    public function testGettingAccessTokenWithClientCredentialsGrantInvalidClientSecret(): void
    {
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make('foobar123');
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => false,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret . 'foo',
                ]
            );


            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayNotHasKey('token_type', $decodedResponse);
            $this->assertArrayNotHasKey('expires_in', $decodedResponse);
            $this->assertArrayNotHasKey('access_token', $decodedResponse);

            $this->assertArrayHasKey('error', $decodedResponse);
            $this->assertSame('invalid_client', $decodedResponse['error']);
            $this->assertArrayHasKey('error_description', $decodedResponse);
            $this->assertSame('Client authentication failed', $decodedResponse['error_description']);
            $this->assertArrayNotHasKey('hint', $decodedResponse);
            $this->assertArrayHasKey('message', $decodedResponse);
            $this->assertSame('Client authentication failed', $decodedResponse['message']);
        });
        $this->assertSame(0, Token::count());
    }

    public function testGettingAccessTokenWithPasswordGrant(): void
    {
        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make($password);
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => true,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($password, $user, $client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'password',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                    'username' => $user->email,
                    'password' => $password,
                ]
            );


            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayHasKey('token_type', $decodedResponse);
            $this->assertArrayHasKey('expires_in', $decodedResponse);
            $this->assertArrayHasKey('access_token', $decodedResponse);
            $this->assertArrayHasKey('refresh_token', $decodedResponse);
            $this->assertSame('Bearer', $decodedResponse['token_type']);
            $expiresInSeconds = 31536000;
            $this->assertEqualsWithDelta($expiresInSeconds, $decodedResponse['expires_in'], 5);

            $jwtAccessToken = \Hyperf\Support\make(Configuration::class)->parser()->parse($decodedResponse['access_token']);
            $this->assertTrue(\Hyperf\Support\make(ClientRepository::class)->findActive($jwtAccessToken->claims()->get('aud'))->is($client));
            $this->assertTrue(\Hyperf\Support\make('auth')->createUserProvider()->retrieveById($jwtAccessToken->claims()->get('sub'))->is($user));

            $token = \Hyperf\Support\make(TokenRepository::class)->find($jwtAccessToken->claims()->get('jti'));
            $this->assertInstanceOf(Token::class, $token);
            $this->assertFalse($token->revoked);
            $this->assertTrue($token->user->is($user));
            $this->assertTrue($token->client->is($client));
            $this->assertNull($token->name);
            $this->assertLessThanOrEqual(5, CarbonImmutable::now()->addSeconds($expiresInSeconds)->diffInSeconds($token->expires_at));
        });
        Assert::assertTrue(true);
    }

    public function testGettingAccessTokenWithPasswordGrantWithInvalidPassword(): void
    {
        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make($password);
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => true,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($password, $user, $client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'password',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                    'username' => $user->email,
                    'password' => $password . 'foo',
                ]
            );

            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayNotHasKey('token_type', $decodedResponse);
            $this->assertArrayNotHasKey('expires_in', $decodedResponse);
            $this->assertArrayNotHasKey('access_token', $decodedResponse);
            $this->assertArrayNotHasKey('refresh_token', $decodedResponse);
            $this->assertArrayNotHasKey('hint', $decodedResponse);

            $this->assertArrayHasKey('error', $decodedResponse);
            $this->assertSame('invalid_grant', $decodedResponse['error']);
            $this->assertArrayHasKey('error_description', $decodedResponse);
            $this->assertArrayHasKey('message', $decodedResponse);
        });

        $this->assertSame(0, Token::count());
    }

    public function testGettingAccessTokenWithPasswordGrantWithInvalidClientSecret(): void
    {
        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make($password);
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => true,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($password, $user, $client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'password',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret . 'foo',
                    'username' => $user->email,
                    'password' => $password,
                ]
            );


            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayNotHasKey('token_type', $decodedResponse);
            $this->assertArrayNotHasKey('expires_in', $decodedResponse);
            $this->assertArrayNotHasKey('access_token', $decodedResponse);
            $this->assertArrayNotHasKey('refresh_token', $decodedResponse);

            $this->assertArrayHasKey('error', $decodedResponse);
            $this->assertSame('invalid_client', $decodedResponse['error']);
            $this->assertArrayHasKey('error_description', $decodedResponse);
            $this->assertSame('Client authentication failed', $decodedResponse['error_description']);
            $this->assertArrayNotHasKey('hint', $decodedResponse);
            $this->assertArrayHasKey('message', $decodedResponse);
            $this->assertSame('Client authentication failed', $decodedResponse['message']);
        });

        $this->assertSame(0, Token::count());
    }

    public function testGettingCustomResponseType(): void
    {
        $passport = \Hyperf\Support\make(Passport::class);
        $passport->authorizationServerResponseType = new IdTokenResponse('foo_bar_open_id_token');

        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = \Hyperf\Support\make(HashManager::class)->make('foobar123');
        $user->save();

        /** @var Client $client */
        $faker = Faker::create();
        $client = Factory::construct($faker)->define(Client::class, function ($faker) use ($user) {
            return [
                'personal_access_client' => false,
                'password_client' => false,
                'name' => $faker->name,
                'secret' => Str::random(40),
                'redirect' => $faker->url,
                'revoked' => false,
                'user_id' => null,
                'provider' => 'users',
            ];
        })->of(Client::class)->create(['user_id' => $user->id]);

        go(function () use ($client) {
            $response = $this->post(
                '/oauth/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $client->id,
                    'client_secret' => $client->secret,
                ]
            );


            $decodedResponse = $response->decodeResponseJson()->json();

            $this->assertArrayHasKey('id_token', $decodedResponse);
            $this->assertSame('foo_bar_open_id_token', $decodedResponse['id_token']);
        });
        Assert::assertTrue(true);
    }

    protected function getUserClass(): string
    {
        return User::class;
    }
}

class User extends Model implements Authenticatable
{
    use HasApiTokens;
    use AuthenticatableTrait;
    use AuthAbility;
}

class IdTokenResponse extends BearerTokenResponse
{
    /**
     * @var string id token
     */
    protected $idToken;

    /**
     * @param string $idToken
     */
    public function __construct($idToken)
    {
        $this->idToken = $idToken;
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        return [
            'id_token' => $this->idToken,
        ];
    }
}
