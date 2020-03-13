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

class Tracker_Migration_V3_ColumnsDao extends DataAccessObject
{

    public function create($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $this->createTemporaryTable($tv5_id);
        $this->sayIfAFieldNeedTwoColumnsOrIsOnTheLeftOrOnTheRight($tv5_id);
        $this->moveFieldsInTheirColumns($tv5_id);
        $this->dropTemporaryTable($tv5_id);
    }

    private function sayIfAFieldNeedTwoColumnsOrIsOnTheLeftOrOnTheRight($tv5_id)
    {
        $this->update("SET @counter  = 0");
        $this->update("SET @previous = NULL");
        $this->update("SET @two_cols = 0");
        $this->update("SET @gcounter = 0");
        $sql = "INSERT INTO temp_tracker_field_$tv5_id (id, parent_id, pos, global_rank)
                SELECT R1.id, R1.parent_id, IF(R1.need_two_cols, '2', IF(R1.position % 2, 'L', 'R')) as pos, R1.global_rank
                FROM (
                    SELECT F.id,
                           F.parent_id,
                           IF(IFNULL(T.cols, 0) + IFNULL(Fl.size, 0) + IFNULL(I.size, 0) + IFNULL(S.size, 0) > 40, 1, 0) AS need_two_cols,
                           @counter := IF(@previous = F.parent_id
                                          AND NOT IF(IFNULL(T.cols, 0) + IFNULL(Fl.size, 0) + IFNULL(I.size, 0) + IFNULL(S.size, 0) > 40, 1, 0)
                                          AND @two_cols = 0, @counter + 1, 1
                                       ) AS position,
                           @gcounter := @gcounter + 1 AS global_rank,
                           @previous := F.parent_id,
                           @two_cols := IF(IFNULL(T.cols, 0) + IFNULL(Fl.size, 0) + IFNULL(I.size, 0) + IFNULL(S.size, 0) > 40, 1, 0)
                    FROM tracker_field AS F
                         LEFT JOIN tracker_field_text AS T ON(F.id = T.field_id)
                         LEFT JOIN tracker_field_float AS Fl ON(F.id = Fl.field_id)
                         LEFT JOIN tracker_field_int AS I ON(F.id = I.field_id)
                         LEFT JOIN tracker_field_string AS S ON(F.id = S.field_id)
                    WHERE parent_id <> 0 AND use_it = 1
                      AND F.tracker_id = $tv5_id
                    ORDER BY parent_id, rank, id
                    ) AS R1";
        $this->update($sql);
    }

    private function moveFieldsInTheirColumns($tv5_id)
    {
        $parent_id = $left = $right = $left_rank = $right_rank = $rank = null;
        $sql = "SELECT *
                FROM temp_tracker_field_$tv5_id
                ORDER BY parent_id, global_rank";
        foreach ($this->retrieve($sql) as $data) {
            if ($parent_id !== $data['parent_id']) {
                $parent_id = $data['parent_id'];
                $left  = null;
                $right = null;
                $nb    = 0;
                $rank  = 1;
                $this->trace('Creating columns for ' . $parent_id);
            }

            if ($data['pos'] == '2') {
                //the field takes 2 columns
                $left     = null;
                $right    = null;
                $new_rank = $rank++;
                $this->trace("{$data['id']} takes 2 columns. change the rank to $new_rank.");
                $sql = "UPDATE tracker_field
                        SET rank = $new_rank
                        WHERE id = {$data['id']}";
                $this->update($sql);
            } else {
                if ($data['pos'] == 'L') {
                    if (!$left) {
                        $left = $this->createColumn($nb++, $parent_id, $rank++);
                        $left_rank  = 1;
                    }
                    $new_parent = $left;
                    $new_rank   = $left_rank++;
                    $this->trace("{$data['id']} will be moved to the left in #$new_parent.");
                } else { //pos = R
                    if (!$right) {
                        $right = $this->createColumn($nb++, $parent_id, $rank++);
                        $right_rank = 1;
                    }
                    $new_parent = $right;
                    $new_rank   = $right_rank++;
                    $this->trace("{$data['id']} will be moved to the right in #$new_parent.");
                }
                $sql = "UPDATE tracker_field
                        SET parent_id = $new_parent,
                            rank      = $new_rank
                            WHERE id = {$data['id']}";
                $this->update($sql);
            }
        }
    }

    private function trace($msg)
    {
        //var_dump($msg);
    }

    private function createColumn($index, $parent_id, $rank)
    {
        $sql = "INSERT INTO tracker_field(parent_id, formElement_type, name, label, rank, tracker_id, use_it)
                SELECT $parent_id, 'column', 'column_$index', 'c$index', $rank, tracker_id, use_it
                FROM tracker_field
                WHERE id = $parent_id";
        $id = $this->updateAndGetLastId($sql);
        $this->trace("c$index with rank $rank has been created #($id)");
        return $id;
    }

    private function createTemporaryTable($tv5_id)
    {
        $sql = "CREATE TABLE temp_tracker_field_$tv5_id (
                    id  INT(11) UNSIGNED NOT NULL PRIMARY KEY,
                    parent_id INT(11) UNSIGNED NOT NULL,
                    pos VARCHAR(1),
                    global_rank INT(11) UNSIGNED
                ) ENGINE=InnoDB";
        $this->update($sql);
    }

    private function dropTemporaryTable($tv5_id)
    {
        $this->update("DROP TABLE temp_tracker_field_$tv5_id");
    }
}
