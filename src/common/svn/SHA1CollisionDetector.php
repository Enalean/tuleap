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

namespace Tuleap\Svn;

class SHA1CollisionDetector
{
    const KNOWN_COLLISION        = 'f92d74e3874587aaf443d1db961d4e26dde13e9c';
    const LENGTH_KNOWN_COLLISION = 320;

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function isColliding($handle_resource)
    {
        $potentially_colliding_part = fread($handle_resource, self::LENGTH_KNOWN_COLLISION);
        if ($potentially_colliding_part === false) {
            throw new \RuntimeException("Can't read the resource to detect a SHA-1 collision");
        }
        return hash_equals(self::KNOWN_COLLISION, sha1($potentially_colliding_part));
    }
}
