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

class LFSObjectPathAllocator
{
    /**
     * @return string
     */
    public function getPathForSaveInProgressObject(LFSObject $lfs_object)
    {
        return 'ongoing-save/' . $lfs_object->getOID()->getValue() . '/' . \bin2hex(\random_bytes(32));
    }

    /**
     * @return string
     */
    public function getPathForReadyToBeAvailableObject(LFSObject $lfs_object)
    {
        return 'ready/' . $lfs_object->getOID()->getValue();
    }

    /**
     * @return string
     */
    public function getPathForAvailableObject(LFSObject $lfs_object)
    {
        $oid_value = $lfs_object->getOID()->getValue();
        return substr($oid_value, 0, 2) . '/' . substr($oid_value, 2, 4) . '/' . $oid_value;
    }
}
