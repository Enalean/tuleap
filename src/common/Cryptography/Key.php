<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Cryptography;

use Tuleap\Cryptography\Exception\CannotSerializeKeyException;

class Key
{
    /**
     * @var string
     */
    private $key_material;

    public function __construct($key_material)
    {
        $this->key_material = $key_material;
    }

    /**
     * @return string
     */
    public function getRawKeyMaterial()
    {
        return $this->key_material;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    public function __debugInfo()
    {
        return array('key_material' => '** protected value**');
    }

    public function __sleep()
    {
        throw new CannotSerializeKeyException();
    }

    public function __wakeup()
    {
        throw new CannotSerializeKeyException();
    }
}
