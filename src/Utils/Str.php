<?php

declare(strict_types=1);
/**
 * This file is part of richard8768/hyperf-passport.
 *
 * @link     https://github.com/richard8768/hyperf-passport
 * @contact  444626008@qq.com
 * @license  https://github.com/richard8768/hyperf-passport/blob/master/LICENSE
 */

namespace Richard\HyperfPassport\Utils;

use DateTimeInterface;
use Hyperf\Stringable\Str as HyperfStr;
use Ramsey\Uuid\Codec\TimestampFirstCombCodec;
use Ramsey\Uuid\Generator\CombGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidInterface;

class Str extends HyperfStr
{
    /**
     * Generate a UUID (version 4).
     */
    public static function uuid(): UuidInterface
    {
        return Uuid::uuid4();
    }

    /**
     * Generate a time-ordered UUID (version 4).
     */
    public static function orderedUuid(?DateTimeInterface $time = null): UuidInterface
    {
        $factory = new UuidFactory();

        $factory->setRandomGenerator(new CombGenerator(
            $factory->getRandomGenerator(),
            $factory->getNumberConverter()
        ));

        $factory->setCodec(new TimestampFirstCombCodec(
            $factory->getUuidBuilder()
        ));

        return $factory->uuid4();
    }
}
