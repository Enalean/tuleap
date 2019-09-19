<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_FieldsetsDao extends DataAccessObject
{

    public function create($tv3_id, $tv5_id)
    {
        $sql = "CREATE TABLE tracker_fieldset_$tv5_id (
                    id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    tracker_id INT( 11 ) UNSIGNED NOT NULL default '0',
                    name TEXT NOT NULL ,
                    description TEXT NOT NULL ,
                    rank INT( 11 ) UNSIGNED NOT NULL default '0',
                    INDEX idx_fk_tracker_id( tracker_id )
                ) ENGINE=InnoDB";
        $this->update($sql);

        $sql = "INSERT INTO tracker_fieldset_$tv5_id(id, tracker_id, name, description, rank)
                SELECT field_set_id,
                    $tv5_id,
                    REPLACE(REPLACE(name, '&gt;', '>'), '&lt;', '<'),
                    REPLACE(REPLACE(description, '&gt;', '>'), '&lt;', '<'),
                    rank
                FROM artifact_field_set
                WHERE group_artifact_id = $tv3_id";
        $this->update($sql);

        // Add cc fieldset
        $sql = "INSERT INTO tracker_fieldset_$tv5_id(tracker_id, name, description, rank)
                SELECT DISTINCT T1.id, 'CC List', 'Dependency links from an artifact to one or several other artifacts', max(rank)+1
                FROM tracker AS T1
                     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset_$tv5_id GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id)";
        $this->update($sql);

        // Add attachments fieldset
        $sql = "INSERT INTO tracker_fieldset_$tv5_id(tracker_id, name, description, rank)
                SELECT DISTINCT T1.id, 'Attachments', 'Attach virtually any piece of information to an artifact in the form of a file', S1.rank
                FROM tracker AS T1
                     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset_$tv5_id GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id)";
        $this->update($sql);

        // Add dependencies fieldset
        $sql = "INSERT INTO tracker_fieldset_$tv5_id(tracker_id, name, description, rank)
                SELECT DISTINCT T1.id, 'Dependencies', 'Establish a dependency link from an artifact to one or several other artifacts belonging to any of the tracker of any project', S1.rank
                FROM tracker AS T1
                     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset_$tv5_id GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id)";
        $this->update($sql);

        // Add references fieldset
        $sql = "INSERT INTO tracker_fieldset_$tv5_id(tracker_id, name, description, rank)
                SELECT DISTINCT T1.id, 'References', 'Cross-reference any artifact, or any other object', S1.rank
                FROM tracker AS T1
                     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset_$tv5_id GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id)";
        $this->update($sql);

        // Add permissions fieldset
        $sql = "INSERT INTO tracker_fieldset_$tv5_id(tracker_id, name, description, rank)
                SELECT DISTINCT T1.id, 'Permissions', 'Restrict access to artifact', S1.rank
                FROM tracker AS T1
                     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset_$tv5_id GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id)";
        $this->update($sql);

        //  Reorder Fieldsets for prepareRanking usage
        $this->update("SET @counter = 0");
        $this->update("SET @previous = NULL");
        $sql = "UPDATE tracker_fieldset_$tv5_id
                        INNER JOIN (SELECT @counter := IF(@previous = tracker_id, @counter + 1, 1) AS new_rank,
                                           @previous := tracker_id,
                                           tracker_fieldset_$tv5_id.*
                                    FROM tracker_fieldset_$tv5_id
                                    ORDER BY tracker_id, rank, id
                        ) as R1 USING(tracker_id,id)
                SET tracker_fieldset_$tv5_id.rank = R1.new_rank";
        $this->update($sql);
    }

    public function nowFieldsetsAreStoredAsField($tv5_id)
    {
        $sql = "INSERT INTO tracker_field(old_id, tracker_id, parent_id, formElement_type, name, label, description, use_it, rank, scope, required)
                SELECT id, tracker_id, 0, 'fieldset', CONCAT('fieldset_', rank), name, description, 1, rank, 'P', 1
                FROM tracker_fieldset_$tv5_id";
        $this->update($sql);

        $sql = "UPDATE tracker_field AS f, tracker_field AS f2
                SET f.parent_id = f2.id
                WHERE f.parent_id = f2.old_id
                 AND f.tracker_id = $tv5_id AND f2.tracker_id = $tv5_id
                 AND f.use_it = 1
                 AND f2.formElement_type = 'fieldset'";
        $this->update($sql);

        $sql = "DROP TABLE tracker_fieldset_$tv5_id";
        $this->update($sql);
    }
}
