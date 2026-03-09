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

use Mockery;

class KeysCommandTest extends PassportTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysAreGenerated(): void
    {
        $this->assertFileExists(self::PUBLIC_KEY);
        $this->assertFileExists(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysShouldNotBeGeneratedTwice(): void
    {
        $outPut = trim($this->runPassportKeys());
        $this->assertSame('Encryption keys already exist. Use the --force option to overwrite them.', $outPut);
    }
}
