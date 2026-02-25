<?php

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
     *
     * @return UuidInterface
     */
    public static function uuid(): UuidInterface
    {
        return Uuid::uuid4();
    }

    /**
     * Generate a time-ordered UUID (version 4).
     *
     * @param DateTimeInterface|null $time
     * @return UuidInterface
     */
    public static function orderedUuid(DateTimeInterface|null $time = null): UuidInterface
    {
        $factory = new UuidFactory;

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
