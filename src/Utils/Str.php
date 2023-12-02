<?php

namespace Richard\HyperfPassport\Utils;

use Hyperf\Utils\Str as HyperfStr;
use Ramsey\Uuid\Codec\TimestampFirstCombCodec;
use Ramsey\Uuid\Generator\CombGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

class Str extends HyperfStr
{
    /**
     * Generate a UUID (version 4).
     *
     * @return \Ramsey\Uuid\UuidInterface
     */
    public static function uuid(): \Ramsey\Uuid\UuidInterface
    {
        return Uuid::uuid4();
    }

    /**
     * Generate a time-ordered UUID (version 4).
     *
     * @param \DateTimeInterface|null $time
     * @return \Ramsey\Uuid\UuidInterface
     */
    public static function orderedUuid(\DateTimeInterface|null $time = null): \Ramsey\Uuid\UuidInterface
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