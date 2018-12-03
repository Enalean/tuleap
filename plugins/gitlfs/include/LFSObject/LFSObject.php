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

namespace Tuleap\GitLFS\LFSObject;

class LFSObject
{
    /**
     * @var LFSObjectID
     */
    private $oid;
    /**
     * @var int
     */
    private $size;

    public function __construct(LFSObjectID $oid, $size)
    {
        $this->oid = $oid;
        if (! \is_int($size)) {
            throw new \TypeError('Expected $size to be an int, got ' . gettype($size));
        }
        if ($size < 0) {
            throw new \UnexpectedValueException('The size must be positive');
        }
        $this->size = $size;
    }

    /**
     * @return LFSObjectID
     */
    public function getOID()
    {
        return $this->oid;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
}
