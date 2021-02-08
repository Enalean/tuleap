<?php
/**
 *  Copyright (c) Maximaster, 2020. All rights reserved
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Permission\FollowUp\PrivateComments;

use Tuleap\DB\DataAccessObject;

class TrackerPrivateCommentsDao extends DataAccessObject
{
    public function updateUgroupsByTrackerId(?int $tracker_id, array $ugroups_ids) : bool
    {
        $data_insert = [];
        foreach ($ugroups_ids as $ugroup_id){
            $data_insert[] = [
                'tracker_id' => $tracker_id,
                'ugroup_id'  => $ugroup_id
            ];
        }
        unset ($ugroup_id);

        $this->getDB()->beginTransaction();
        try {
            $this->getDB()->delete(
                'tracker_private_comment_permission',
                ['tracker_id' => $tracker_id]
            );
            $this->getDB()->insertMany(
                'tracker_private_comment_permission',
                $data_insert
            );
        } catch (\PDOException $exception) {
            $this->getDB()->rollBack();
            return false;
        }
        $this->getDB()->commit();
        return true;
    }

    public function deleteUgroupsByTrackerId($tracker_id): void
    {
        $this->getDB()->delete(
            'tracker_private_comment_permission',
            ['tracker_id' => $tracker_id]
        );
    }

    public function getAccessUgroupsByTrackerId($tracker_id)
    {
        $sql = "SELECT *
                FROM tracker_private_comment_permission
                WHERE tracker_id = ?";
        return $this->getDB()->run($sql, $tracker_id);
    }
}
