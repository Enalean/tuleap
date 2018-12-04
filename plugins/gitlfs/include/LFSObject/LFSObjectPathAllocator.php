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

class LFSObjectPathAllocator
{
    /**
     * @return string
     */
    public function getBasePathForSaveInProgressObject()
    {
        return 'ongoing-save/';
    }

    /**
     * @return string
     */
    public function getPathForSaveInProgressObject(\GitRepository $repository, LFSObject $lfs_object)
    {
        return $this->getBasePathForSaveInProgressObject() .
            $lfs_object->getOID()->getValue() . '/' . $repository->getId() . '/' . \bin2hex(\random_bytes(32));
    }

    /**
     * @return string
     */
    public function getBasePathForReadyToBeAvailableObject()
    {
        return 'ready/';
    }

    /**
     * @return string
     */
    public function getPathForReadyToBeAvailableObject(\GitRepository $repository, LFSObject $lfs_object)
    {
        return $this->getBasePathForReadyToBeAvailableObject() .
            $lfs_object->getOID()->getValue() . '/' . $repository->getId();
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
