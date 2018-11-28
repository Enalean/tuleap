<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Object;

class LFSObjectID
{
    /**
     * @var string
     */
    private $value;

    public function __construct($oid_value)
    {
        if (! \is_string($oid_value)) {
            throw new \TypeError('Expected $oid to be a string, got ' . gettype($oid_value));
        }
        if (preg_match('/^[a-fA-F0-9]{64}$/', $oid_value) !== 1) {
            throw new \UnexpectedValueException('OID is invalid');
        }
        $this->value = $oid_value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
