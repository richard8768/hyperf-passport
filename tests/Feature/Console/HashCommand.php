<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Tests\Feature\Console;

use Hyperf\Stringable\Str;
use HyperfExt\Hashing\HashManager;
use Richard\HyperfPassport\Client;
use Richard\HyperfPassport\Passport;
use Richard\HyperfPassport\Tests\Feature\PassportTestCase;

class HashCommand extends PassportTestCase
{
    public function testItCanProperlyHashClientSecrets(): void
    {
        $client = \Hyperf\Support\make(Client::class)->create(['secret' => $secret = Str::random(40)]);
        $hasher = \Hyperf\Support\make(HashManager::class);

        $passport = \Hyperf\Support\make(Passport::class);
        $passport->hashesClientSecrets = true;
        //$passport->hashClientSecrets();

        $this->runPassportHash();

        $this->assertTrue($hasher->check($secret, $client->refresh()->secret));

        $passport->hashesClientSecrets = false;
    }
}
