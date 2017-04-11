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
    const SHA1COLLISIONDETECTOR_PATH = '/usr/bin/sha1collisiondetector';
    const SUCCESS_EXIT_CODE          = 0;

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function isColliding($handle_resource)
    {
        $handle_sha1collisiondetector = popen(self::SHA1COLLISIONDETECTOR_PATH, 'w');
        if ($handle_sha1collisiondetector === false) {
            throw new \RuntimeException("Can't open a process file pointer to " . self::SHA1COLLISIONDETECTOR_PATH);
        }

        $size_data_copied = stream_copy_to_stream($handle_resource, $handle_sha1collisiondetector);
        if ($size_data_copied === false) {
            throw new \RuntimeException("Can't read the resource to detect a SHA-1 collision");
        }

        $exit_status = pclose($handle_sha1collisiondetector);

        return $exit_status !== self::SUCCESS_EXIT_CODE;
    }
}
