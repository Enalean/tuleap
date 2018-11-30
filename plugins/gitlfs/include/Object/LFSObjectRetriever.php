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

class LFSObjectRetriever
{
    /**
     * @var LFSObjectDAO
     */
    private $dao;

    public function __construct(LFSObjectDAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return LFSObject[]
     */
    public function getExistingLFSObjectsFromTheSetForRepository(\GitRepository $repository, LFSObject ...$lfs_objects)
    {
        $objects_by_oid_value = [];
        foreach ($lfs_objects as $lfs_object) {
            $objects_by_oid_value[$lfs_object->getOID()->getValue()] = $lfs_object;
        }

        $existing_lfs_object_rows = $this->dao->searchByRepositoryIDAndOIDs(
            $repository->getId(),
            array_keys($objects_by_oid_value)
        );

        $existing_lfs_objects = [];
        foreach ($existing_lfs_object_rows as $existing_lfs_object_row) {
            $existing_lfs_objects[] = $objects_by_oid_value[$existing_lfs_object_row['object_oid']];
        }

        return $existing_lfs_objects;
    }

    /**
     * @return bool
     */
    public function doesLFSObjectExists(LFSObject $lfs_object)
    {
        return $this->dao->searchByOIDValue($lfs_object->getOID()->getValue()) !== null;
    }
}
