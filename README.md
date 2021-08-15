# richard/hyperf-passport

hyperf 的 hyperf-passport 组件，支持对多种用户进行登录授权支持Oauth2.0的四种授权模式，目前密码授权模式已完全可用。
本组件参考了 laravel 的 passport 组件设计，使用体验大体和 laravel 的 passport 差不多。

> 任何问题请加QQ提问：444626008

## 安装前的准备 - before install

PHP>=7.3
安装依赖包
```bash
#授权依赖包
$ composer require 96qbhy/hyperf-auth
$ php bin/hyperf.php vendor:publish 96qbhy/hyperf-auth
#加密依赖包
$ composer require hyperf-ext/encryption
$ php bin/hyperf.php vendor:publish hyperf-ext/encryption
$ composer require hyperf-ext/hashing
$ php bin/hyperf.php vendor:publish hyperf-ext/hashing
#模板引擎和视图
$ composer require hyperf/view-engine
$ composer require hyperf/view
#hyperf的session
$ composer require hyperf/session
$ php bin/hyperf.php vendor:publish hyperf/session
```
使用 php bin/hyperf.php gen:key 命令来生成密钥,并将KEY值复制到文件 config/autoload/encryption.php中的env('AES_KEY', 'place_to_hold_key')

编辑文件config/autoload/view.php配置视图默认引擎:
```
<?php
declare(strict_types=1);

use Hyperf\View\Mode;
use Hyperf\View\Engine\BladeEngine;

return [
    // 使用的渲染引擎
    'engine' => BladeEngine::class,
    // 不填写则默认为 Task 模式，推荐使用 Task 模式
    'mode' => Mode::TASK,
    'config' => [
        // 若下列文件夹不存在请自行创建
        'view_path' => BASE_PATH . '/storage/view/',
        'cache_path' => BASE_PATH . '/runtime/view/',
    ],
];
?>
```
在文件 config/autoload/middlewares.php中添加全局中间件 
```
<?php
return [
    // 这里的 http 对应默认的 server name，如您需要在其它 server 上使用 Session，需要对应的配置全局中间件
    'http' => [
        \Hyperf\Session\Middleware\SessionMiddleware::class,
    ],
];
?>
```


## 安装 - install


```bash
$ composer require richard/hyperf-passport
php bin/hyperf.php vendor:publish richard/hyperf-passport
```

## 配置 - configuration


编辑文件 config/autoload/auth.php

在文件中引入Provicer和Guard

use Richard\HyperfPassport\PassportUserProvider;

use Richard\HyperfPassport\Guard\TokenGuard;

在guards里面填写

'passport' => [
    'driver' => TokenGuard::class,
    'provider' => 'users',
]

并在users里面定义相应的provider

'users' => [
    'driver' => PassportUserProvider::class, 
	
    'model' => App\Model\User::class,
]

以下为auth.php文件样板

```
<?php

declare(strict_types=1);

use HPlus\Admin\Model\Admin\Administrator;
use Qbhy\HyperfAuth\Provider\EloquentProvider;
use Qbhy\SimpleJwt\Encoders\Base64UrlSafeEncoder;
use Qbhy\SimpleJwt\EncryptAdapters\PasswordHashEncrypter;
use Richard\HyperfPassport\PassportUserProvider;
use Richard\HyperfPassport\Guard\TokenGuard;

return [
    'default' => [
        'guard' => 'jwt',
        'provider' => 'admin',
    ],
    'guards' => [// 开发者可以在这里添加自己的 guard ，guard Qbhy\HyperfAuth\AuthGuard 接口
        'jwt' => [
            'driver' => Qbhy\HyperfAuth\Guard\JwtGuard::class,
            'provider' => 'admin',
            'secret' => env('JWT_SECRET', 'hyperf.plus'),
            'ttl' => 60 * 60, // 单位秒
            'default' => PasswordHashEncrypter::class,
            'encoder' => new Base64UrlSafeEncoder(),
            'cache' => function () {
                return make(Qbhy\HyperfAuth\HyperfRedisCache::class);
            },
        ],
        'session' => [
            'driver' => Qbhy\HyperfAuth\Guard\SessionGuard::class,
            'provider' => 'users',
        ],
        'passport' => [
            'driver' => TokenGuard::class,
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'admin' => [
            'driver' => EloquentProvider::class, // user provider 需要实现 Qbhy\HyperfAuth\UserProvider 接口
            'model' => Administrator::class, //  需要实现 Qbhy\HyperfAuth\Authenticatable 接口
        ],
        'users' => [
            'driver' => PassportUserProvider::class, // user provider 需要实现 Qbhy\HyperfAuth\UserProvider 接口
            'model' => App\Model\User::class, //  需要实现 Qbhy\HyperfAuth\Authenticatable 接口
        ],
        'merchants' => [
            'driver' => PassportUserProvider::class, // user provider 需要实现 Qbhy\HyperfAuth\UserProvider 接口
            'model' => App\Model\Merchant::class, //  需要实现 Qbhy\HyperfAuth\Authenticatable 接口
        ],
    ],
];
?>
```
执行迁移

php bin/hyperf.php migrate

php bin/hyperf.php migrate:status

安装passport

php bin/hyperf.php passport:install  --force --length=4096

php bin/hyperf.php passport:purge

你还可以根据providers配置项里面的元素生成client

php bin/hyperf.php passport:client --password --name="your client name"


如果有数据填充文件可以执行 php bin/hyperf.php db:seed --path=seeders/user_table_seeder.php

其中seeders/user_table_seeder.php为填充文件路径

注意填充文件中密码的格式为 \HyperfExt\Hashing\Hash::make('your password'),否则会导致passport密码校验失败


在用户模型中引入\Richard\HyperfPassport\HasApiTokens和\Richard\HyperfPassport\Auth\AuthenticatableTrait以及\Qbhy\HyperfAuth\AuthAbility

用户登录默认是验证email如果希望验证其他字段可以在模型中添加findForPassport方法，然后编写自己的代码逻辑

用户密码默认存储字段是password如果希望验证其他字段可以在模型中添加getAuthPassword方法，然后返回自己的密码字段

以下为模型文件User.php样板

```
<?php

declare (strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;
use Richard\HyperfPassport\Auth\AuthenticatableTrait;
use Richard\HyperfPassport\HasApiTokens;

/**
 */
class User extends Model implements Authenticatable {

    use HasApiTokens;
    use AuthenticatableTrait;
    use AuthAbility;

    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'member';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];


    /**
     * 修改认证时的默认username字段
     */
    public function findForPassport($username) {
        if (strpos($username, '@') !== false) {
            return $this->findByEmailForPassport($username);
        } else {
            if ((is_numeric($username)) && (strlen($username) == 11)) {
                return $this->findByMobileForPassport($username);
            } else {
                return $this->findByUsernameForPassport($username);
            }
        }
    }

    protected function findByEmailForPassport($username) {
        return $this->where('email', $username)->first();
    }

    protected function findByMobileForPassport($username) {
        return $this->where('mobile', $username)->first();
    }

    protected function findByUsernameForPassport($username) {
        return $this->where('member_name', $username)->first();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword() {
        return $this->member_pass;
    }

}
?>
```


## 使用 - usage


> 以下是伪代码，仅供参考。
在文件config/autoload/exceptions.php中添加全局异常处理器
```
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
return [
    'handler' => [
        'http' => [
            \HPlus\Admin\Exception\Handler\AppExceptionHandler::class,
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
            \Richard\HyperfPassport\PassportExceptionHandler::class,
        ],
    ],
];
?>
```
##### 接口名称

- 登录以获取会话令牌和刷新令牌

##### 请求地址
- ` /oauth/token `
  
##### 请求方式
- POST 

##### 参数

|参数名|必选|类型|说明|
|:----    |:---|:----- |-----   |
username |是  |string |用户名/邮箱/手机号  |
password |是  |string |用户密码  |
grant_type |是  |string |授权类型 一般填password  |
client_id |是  |string |服务端分配的client_id  |
client_secret |是  |string |服务端分配的client_id对应的密钥  |

##### 响应信息 

``` 
{
    "token_type": "Bearer",
    "expires_in": 31536000,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiMjFhZWY3ODJkNjhhMjE3YmQzYTg1YTFlMTY3MWYwMjBmMzIxOGIwMTNlZDA4ZmNlMjIzYTFiNGFkODM1ZGY2MDE3YThjODg0YjU3ZDVhMDUiLCJpYXQiOjE2Mjg1NjkzMzQsIm5iZiI6MTYyODU2OTMzNCwiZXhwIjoxNjYwMTA1MzM0LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.Z2PCWbJeL4NsDtJqLyER-Gg0Kf-DV-66mV91bVLryXJtl3YBhZcPsALQZt5WVTa4gC2s-GBOvMBnREZeJ4RfBaBKoBmEF1_6Cj1I8u0zdazF3mGh-V5JpiDJw73XmdQQ61lqiOp0Lxx34H7ZSkhGYhn9QqSkHmqtrRT_8NYY-bJ1GOE4i0EO-ckFqrHhtvWrVooZ5eN3SkxG3bEf24LLuvCj9EhtKPdF818dYjjWxiA2pl_3OAakQDOHTVh1MhvFW1TTnjBGf0aG2_7gcZWyFzJydx59U_8knOvIS5upTB9aP13q2dXGTGf9Q-1EvVDLNyxB_ppDCkCogc7daTkgs_aqvoC1EsC5pqPDZQDCO5RbVcJ881GGNdjtrmud7qapc9HO7e6JZSKg_cx72IB8jziKwWiqTMJMtDrlaWJ4gkGMO5MeEnberIp6J6Yut0iWR6CUWVBTDPymeOpdbZqpwINhcFh4qq_YSNh8IE9tW9-HbYi6NrAX1I1KSaDWgHI9m56nkY2afT8le0IbEJ5AjwcWBATuQbJfj3S2jfyIembJoq9egKeGMrG9KADM151phFR1h6vItJMlGbjwMx6Pry6E5fvQUIxfi3N5-k-ptfKEsb2x-ENffzg_W8aeEEbObt4kV7OczxKGGdGqk3WY7_suASyEkN-7oEiUd77EJps",
    "refresh_token": "def50200961f6b024f098f4ef9416c07515a6449f5feb7e3db6e2e19f4bb260f2758e9bc71b81e49587a96f83658c05f8b93243a6cd5342f1a6a7eee8582e3dedab8915ce24f41875077cd5e22926a53ea0b4675eeb86f3322285848cbac96086eedb0782d6d99a8f9bbe39bcdf1c3215ae127e0a40a9536bdb3496e36f03026015ebf88c81c1a860c6c15a8a48edc7bc8c4a150948b1cfad76c29b01e403711a25f6a6969aaec0777ef6919a7fc707ea63c780e744ceb593f8d7cfd8aef7af59769f1ba5be7b6479c45cdd1c15d3827dd6ba4d0193bead299840c4fea66356a56e2ca407add2d904b1a97a4f0977ad4fcc256cc8f805d3e1fe0379e77478c32d2c22b3f3b31ac289645873cae6de46fa50523238826942846746b0ee4270e6dffcd79994b14a939ada51af7afcc86047f5350b178f0a1d18ba4a3c72b5327dd366a4224252571e1a238fd11748703dbd439f620809b6706fd0d485c29b5c04feb"
}
```
##### 接口名称

- 使用刷新令牌获得新的会话令牌和刷新令牌

##### 请求地址
- ` /oauth/token `
  
##### 请求方式
- POST 

##### 参数

|参数名|必选|类型|说明|
|:----    |:---|:----- |-----   |
grant_type |是  |string |授权类型 refresh_token  |
refresh_token |是  |string |登录获得的刷新令牌  |
client_id |是  |string |服务端分配的client_id  |
client_secret |是  |string |服务端分配的client_id对应的密钥  |

##### 响应信息 

``` 
{
    "token_type": "Bearer",
    "expires_in": 31536000,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiZTAwYzY5ZmU1YzJmZjJhY2RlYjU5ZGU5YTY3YzIwOTNiOWNmZGJmNjFjYWIwMDg5OWNmYTI1NWJiZWVhYmNlYTJhN2JiNGFhNTUyMzg3NzEiLCJpYXQiOjE2Mjg1Njk0ODgsIm5iZiI6MTYyODU2OTQ4OCwiZXhwIjoxNjYwMTA1NDg4LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.qljYkIJUOkrMFsdxrMrieuj9uanUMo5lKqwvWBvg1cvHFvjXA0FxhTb6cnnKNKdUCFmKCIwWhCY-MleNDy5rso5NF_1EWiWTmaWgpGibVZvbuvrPSL6md8OWiMp3WBa-twO0F-YGkinRu2zycpi_3eVFp5OLL1vWTsOPNuUAHIqWc0bOjoKdGLy8z3bGY3K6iOzBDk2E9FNZG-af8ZDL-cA-0wDcsLRibexBon8rzNbpCK_rmuk8tm2u4xiEQ8xtvJysQ5d8vm19oUpXY61WkiePXbJolxpctCJAkmyZftwFIH1J9nQbLXxyeDenWqkB5Yi2L8wbgmf6y4xhNfDCDvNlionJl32COeT90zj0CUijQyUy6xrUW_kieC2OIjQ6FWLfMA_tMg2RmoS4BAyQah9cFq2B9g0K-SkVyQKpm7Tb7oTqJ3b3mMcL_SYGcwQH5QrAAFI0ngiHMdXJKW_HAcwV5qycQDRkeSqdkNExqawSeFVuM9xqstv8Q-y0i2Y4MmweBo8WHi4UL5NdALGKeK-rT_vQePcb-6l30d3PODH4UpZKwTddfNaP60m3OAqSOP4b1ZeV9shLSOotdUobDHzI3Y1Geujv30uphp1FyvPdsxKUba7o_94GOAsj7ggIAY1K5VLdjpUF0AxL611BRTWZ2NA5whwPbGOg5WGQpOc",
    "refresh_token": "def50200c3c742e60906d59ef5f9628de44af8cf2fbc77b2782c540ed0c9a98a149b2c134d6156ec38f8ea340f09aa096623e0fb7000dfb6169c140d5ed08ebe50f0daa5d186fd05937e35f1bcdd4be81cc01e6bf9d5dcf32dbeeb124e1a729acf089089f7a2ab53d94ebdacb020834b831b6b9bba56e644eb0a320ebe2ce1cbdaf5c825b195396782ab0d8d8967d68e8edc9052d276d72f4f62529182fd054cc9f150ef84ae8f2aa895a62ae109e432bc045b7d5afeb6f9d0b0a44c9de7a2a84f298354baed67728fa57e866af742b6a22f0deaa022a446060c6e339e9151ab1adf118f76e9b5738d00c9fa67c3293399f6ca2c844eaa75d08ca21e592d45f5ac53b433344590ba5c0658f1891a7b9cbe4cd917183af060858813702ca5c0c1d0d296927dc6514a595c4fa02dcde225229881ba9ff8068d8f049004c752f00e715ac4761b34113b716e53142a9449e9f6274b489a5256022f53917673d21e8de4"
}
```

获得用户信息

由于passport支持多种类型用户,clientid是基于provicder发放的所以获取用户信息时需要提供client

请求头信息

Authorization Bearer access_token(注意Bearer后面含有空格)

X-Client-Id client_id

控制器代码
```
<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Richard\HyperfPassport\PassportAuthMiddleware;
use Richard\HyperfPassport\AuthManager;

/**
 * @Controller
 */
class DemoController extends AbstractController {

    /**
     * @Inject
     * @var AuthManager
     */
    protected $auth;

    /**
     * @Middlewares({
     *    @Middleware(PassportAuthMiddleware::class)
     * })
     * @RequestMapping(path="index", methods="get,post,options")
     */
    public function index(RequestInterface $request, ResponseInterface $response) {
        $user = $this->auth->guard('passport')->user();
        //var_dump($user);
        $userId = (int) $user->getId();
        return ['user_id' => $userId];
    }


}
?>
```
