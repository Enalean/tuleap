<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class CrossReferenceDao extends DataAccessObject
{

    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'cross_references';
    }

    public function updateTargetKeyword($old_keyword, $keyword, $group_id)
    {
        $sql = sprintf(
            "UPDATE $this->table_name SET target_keyword=%s WHERE target_keyword= %s and target_gid=%s",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($old_keyword),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function updateSourceKeyword($old_keyword, $keyword, $group_id)
    {
        $sql = sprintf(
            "UPDATE $this->table_name SET source_keyword=%s WHERE source_keyword= %s and source_gid=%s",
            $this->da->quoteSmart($keyword),
            $this->da->quoteSmart($old_keyword),
            $this->da->quoteSmart($group_id)
        );
        return $this->update($sql);
    }

    public function deleteEntity($id, $nature, $group_id)
    {
        $id       = $this->da->escapeInt($id);
        $nature   = $this->da->quoteSmart($nature);
        $group_id = $this->da->escapeInt($group_id);

        $sql = "DELETE FROM $this->table_name
                WHERE (source_type = $nature AND source_id = $id AND source_gid = $group_id)
                   OR (target_type = $nature AND target_id = $id AND target_gid = $group_id)";
        return $this->update($sql);
    }

    public function createDbCrossRef($cross_ref)
    {
        $sql = "INSERT INTO {$this->table_name}
              (created_at,user_id,source_type,source_keyword,source_id,
               source_gid,target_type,target_keyword, target_id,target_gid)
              VALUES ( " .
                (time()) . "," .
                $this->da->quoteSmart((int) $cross_ref->userId) . ", " .
                $this->da->quoteSmart((string) $cross_ref->insertSourceType) . ", " .
                $this->da->quoteSmart((string) $cross_ref->sourceKey) . " ," .
                $this->da->quoteSmart((string) $cross_ref->refSourceId) . " ," .
                $this->da->quoteSmart((int) $cross_ref->refSourceGid) . ", " .
                $this->da->quoteSmart((string) $cross_ref->insertTargetType) . ", " .
                $this->da->quoteSmart((string) $cross_ref->targetKey) . " ," .
                $this->da->quoteSmart((string) $cross_ref->refTargetId) . ", " .
                $this->da->quoteSmart((int) $cross_ref->refTargetGid) . ")";

        $res = $this->da->query($sql);
        return (bool) ($res && !$res->isError());
    }

    public function existInDb($cross_ref)
    {
        $sql = "SELECT * from {$this->table_name} WHERE " .
              "source_id='" . db_es($cross_ref->refSourceId) . "' AND " .
              "target_id='" . db_es($cross_ref->refTargetId) . "' AND " .
              "source_gid='" . db_ei($cross_ref->refSourceGid) . "' AND " .
              "target_gid='" . db_ei($cross_ref->refTargetGid) . "' AND " .
              "source_type='" . db_es($cross_ref->insertSourceType) . "' AND " .
              "target_type='" . db_es($cross_ref->insertTargetType) . "'";
        $res = $this->da->query($sql);
        return (bool) ($res && !$res->isError() && $res->rowCount() >= 1);
    }

    public function fullReferenceExistInDb($cross_ref)
    {
        $sql = "SELECT * from {$this->table_name} WHERE " .
            "source_id='" . db_es($cross_ref->refSourceId) . "' AND " .
            "target_id='" . db_es($cross_ref->refTargetId) . "' AND " .
            "source_gid='" . db_ei($cross_ref->refSourceGid) . "' AND " .
            "target_gid='" . db_ei($cross_ref->refTargetGid) . "' AND " .
            "source_type='" . db_es($cross_ref->insertSourceType) . "' AND " .
            "target_keyword='" . db_es($cross_ref->targetKey) . "' AND " .
            "target_type='" . db_es($cross_ref->insertTargetType) . "'";
        $res = $this->da->query($sql);

        return (bool) ($res && ! $res->isError() && $res->rowCount() >= 1);
    }

    public function deleteCrossReference($cross_ref)
    {
        $target_group_id = $this->da->escapeInt($cross_ref->refTargetGid);
        $target_id       = $this->da->quoteSmart($cross_ref->refTargetId);
        $target_ref_type = $this->da->quoteSmart($cross_ref->refTargetType);

        $source_group_id = $this->da->escapeInt($cross_ref->refSourceGid);
        $source_id       = $this->da->quoteSmart($cross_ref->refSourceId);
        $source_type     = $this->da->quoteSmart($cross_ref->refSourceType);

        $sql = "DELETE FROM {$this->table_name} WHERE
                ( ( target_gid  = $target_group_id AND
                    target_id   = $target_id AND
                    target_type = $target_ref_type
                  )
                  AND
                  ( source_gid  = $source_group_id AND
                    source_id   = $source_id AND
                    source_type = $source_type
                  )
                )
                OR
                ( ( target_gid  = $target_group_id AND
                    target_id   = $target_id AND
                    target_type = $target_ref_type
                  )
                  AND
                  ( source_gid  = $source_group_id AND
                    source_id   = $source_id AND
                    source_type = $source_type
                  )
                )";
        $res = $this->da->query($sql);

        return (bool) $res;
    }

    public function deleteFullCrossReference(CrossReference $cross_ref)
    {
        $target_group_id = $this->da->escapeInt($cross_ref->refTargetGid);
        $target_id       = $this->da->quoteSmart($cross_ref->refTargetId);
        $target_ref_type = $this->da->quoteSmart($cross_ref->refTargetType);
        $target_keyword  = $this->da->quoteSmart($cross_ref->targetKey);

        $source_group_id = $this->da->escapeInt($cross_ref->refSourceGid);
        $source_id       = $this->da->quoteSmart($cross_ref->refSourceId);
        $source_type     = $this->da->quoteSmart($cross_ref->refSourceType);

        $sql = "DELETE FROM {$this->table_name} WHERE
                ( ( target_gid     = $target_group_id AND
                    target_id      = $target_id AND
                    target_type    = $target_ref_type AND
                    target_keyword = $target_keyword
                  )
                  AND
                  ( source_gid  = $source_group_id AND
                    source_id   = $source_id AND
                    source_type = $source_type
                  )
                )
                OR
                ( ( target_gid     = $target_group_id AND
                    target_id      = $target_id AND
                    target_type    = $target_ref_type AND
                    target_keyword = $target_keyword
                  )
                  AND
                  ( source_gid  = $source_group_id AND
                    source_id   = $source_id AND
                    source_type = $source_type
                  )
                )";

        $res = $this->da->query($sql);

        return (bool) $res;
    }

    /**
     * @return array|false
     */
    public function getReferenceByKeyword(string $keyword)
    {
        $keyword = $this->da->quoteSmart($keyword);
        $sql     = "SELECT *
            FROM $this->table_name
            WHERE source_keyword = $keyword";

        return $this->retrieveFirstRow($sql);
    }
}
