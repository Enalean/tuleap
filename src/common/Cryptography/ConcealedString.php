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

class ConcealedString
{
    /**
     * @var string
     */
    private $value;

    public function __construct($value)
    {
        if (! is_string($value)) {
            throw new \TypeError('Expected $value to be a string, got ' . gettype($value));
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    public function __debugInfo()
    {
        return array('value' => '** protected value, invoke getString instead of trying to dump it **');
    }
}
