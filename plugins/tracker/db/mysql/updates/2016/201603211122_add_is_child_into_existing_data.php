<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201603211122_add_is_child_into_existing_data extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add _is_child nature into existing artifact links #2';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id)
                    INNER JOIN tracker_hierarchy AS hierarchy
                        ON (hierarchy.parent_id = parent_art.tracker_id
                            AND hierarchy.child_id = child_art.tracker_id)
                SET nature = '_is_child'";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError('An error occured while adding nature into existing artifact links');
        }
    }
}
