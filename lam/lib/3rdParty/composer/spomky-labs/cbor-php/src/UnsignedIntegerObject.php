<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use InvalidArgumentException;

final class UnsignedIntegerObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = 0b000;

    /**
     * @var string|null
     */
    private $data;

    public function __construct(int $additionalInformation, ?string $data)
    {
        parent::__construct(self::MAJOR_TYPE, $additionalInformation);
        $this->data = $data;
    }

    public static function createObjectForValue(int $additionalInformation, ?string $data): self
    {
        return new self($additionalInformation, $data);
    }

    public static function create(int $value): self
    {
        return self::createFromGmpValue(gmp_init($value));
    }

    public static function createFromGmpValue(\GMP $value): self
    {
        if (gmp_cmp($value, gmp_init(0)) < 0) {
            throw new InvalidArgumentException('The value must be a positive integer.');
        }

        switch (true) {
            case gmp_cmp($value, gmp_init(24)) < 0:
                $ai = gmp_intval($value);
                $data = null;
                break;
            case gmp_cmp($value, gmp_init('FF', 16)) < 0:
                $ai = 24;
                $data = self::hex2bin(str_pad(gmp_strval($value, 16), 2, '0', STR_PAD_LEFT));
                break;
            case gmp_cmp($value, gmp_init('FFFF', 16)) < 0:
                $ai = 25;
                $data = self::hex2bin(str_pad(gmp_strval($value, 16), 4, '0', STR_PAD_LEFT));
                break;
            case gmp_cmp($value, gmp_init('FFFFFFFF', 16)) < 0:
                $ai = 26;
                $data = self::hex2bin(str_pad(gmp_strval($value, 16), 8, '0', STR_PAD_LEFT));
                break;
            default:
                throw new InvalidArgumentException('Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.');
        }

        return new self($ai, $data);
    }

    public function getMajorType(): int
    {
        return self::MAJOR_TYPE;
    }

    public function getAdditionalInformation(): int
    {
        return $this->additionalInformation;
    }

    public function getValue(): string
    {
        return $this->getNormalizedData();
    }

    public function getNormalizedData(bool $ignoreTags = false): string
    {
        if (null === $this->data) {
            return \strval($this->additionalInformation);
        }

        return gmp_strval(gmp_init(bin2hex($this->data), 16), 10);
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        if (null !== $this->data) {
            $result .= $this->data;
        }

        return $result;
    }

    private static function hex2bin(string $data): string
    {
        $result = hex2bin($data);
        if (false === $result) {
            throw new InvalidArgumentException('Unable to convert the data');
        }

        return $result;
    }
}
