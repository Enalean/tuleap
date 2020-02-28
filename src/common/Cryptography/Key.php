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

use Tuleap\Cryptography\Exception\CannotSerializeKeyException;

class Key
{
    /**
     * @var string
     */
    private $key_material;

    public function __construct(ConcealedString $key_data)
    {
        $this->key_material = $key_data->getString();
    }

    public function getRawKeyMaterial() : string
    {
        return $this->key_material;
    }

    public function __toString(): string
    {
        return '';
    }

    public function __debugInfo() : array
    {
        return ['key_material' => '** protected value**'];
    }

    public function __sleep() : array
    {
        throw new CannotSerializeKeyException();
    }

    public function __wakeup() : void
    {
        throw new CannotSerializeKeyException();
    }

    public function __destruct()
    {
        \sodium_memzero($this->key_material);
    }
}
