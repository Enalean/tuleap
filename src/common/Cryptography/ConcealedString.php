<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Cryptography;

use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

/**
 * @psalm-immutable
 */
final class ConcealedString
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getString() : string
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return $this->value;
    }

    public function __debugInfo() : array
    {
        return ['value' => '** protected value, invoke getString instead of trying to dump it **'];
    }

    public function __sleep()
    {
        self::throwSerializationException();
    }

    public function __wakeup()
    {
        self::throwSerializationException();
    }

    /**
     * @psalm-mutation-free
     */
    private static function throwSerializationException(): void
    {
        throw new \LogicException(
            'A concealed string is not supposed to be serialized directly, if need to do so please call ' .
            SymmetricCrypto::class . '::encrypt() and ' . SymmetricCrypto::class . '::decrypt()'
        );
    }

    public function isIdenticalTo(ConcealedString $string_b): bool
    {
        return \hash_equals($string_b->value, $this->value);
    }

    public function __destruct()
    {
        /**
         * While is indeed correct about this, it is only an issue if a developer manually call __destruct() (please don't)
         * In the expected object lifecycle this method will only called when the object will not be reused
         * again so mutability is not a problem.
         * @psalm-suppress ImpureFunctionCall
         * @psalm-suppress InaccessibleProperty
         */
        \sodium_memzero($this->value);
    }
}
