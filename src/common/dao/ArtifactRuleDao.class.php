<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 *  Data Access Object for ArtifactRule
 */
class ArtifactRuleDao extends DataAccessObject
{
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM artifact_rule";
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by Id
    * @return DataAccessResult
    */
    public function searchById($id)
    {
        $sql = sprintf(
            "SELECT group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id FROM artifact_rule WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by GroupArtifactId
    * @return DataAccessResult
    */
    public function searchByGroupArtifactId($groupArtifactId)
    {
        $sql = sprintf(
            "SELECT id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id FROM artifact_rule WHERE group_artifact_id = %s",
            $this->da->quoteSmart($groupArtifactId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by SourceFieldId
    * @return DataAccessResult
    */
    public function searchBySourceFieldId($sourceFieldId)
    {
        $sql = sprintf(
            "SELECT id, group_artifact_id, source_value_id, target_field_id, rule_type, target_value_id FROM artifact_rule WHERE source_field_id = %s",
            $this->da->quoteSmart($sourceFieldId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by SourceValueId
    * @return DataAccessResult
    */
    public function searchBySourceValueId($sourceValueId)
    {
        $sql = sprintf(
            "SELECT id, group_artifact_id, source_field_id, target_field_id, rule_type, target_value_id FROM artifact_rule WHERE source_value_id = %s",
            $this->da->quoteSmart($sourceValueId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by TargetFieldId
    * @return DataAccessResult
    */
    public function searchByTargetFieldId($targetFieldId)
    {
        $sql = sprintf(
            "SELECT id, group_artifact_id, source_field_id, source_value_id, rule_type, target_value_id FROM artifact_rule WHERE target_field_id = %s",
            $this->da->quoteSmart($targetFieldId)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by RuleType
    * @return DataAccessResult
    */
    public function searchByRuleType($ruleType)
    {
        $sql = sprintf(
            "SELECT id, group_artifact_id, source_field_id, source_value_id, target_field_id, target_value_id FROM artifact_rule WHERE rule_type = %s",
            $this->da->quoteSmart($ruleType)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches ArtifactRule by TargetValueId
    * @return DataAccessResult
    */
    public function searchByTargetValueId($targetValueId)
    {
        $sql = sprintf(
            "SELECT id, group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type FROM artifact_rule WHERE target_value_id = %s",
            $this->da->quoteSmart($targetValueId)
        );
        return $this->retrieve($sql);
    }


    /**
    * create a row in the table artifact_rule
    * @return false|int id(auto_increment) if there is no error
    */
    public function create($group_artifact_id, $source_field_id, $source_value_id, $target_field_id, $rule_type, $target_value_id)
    {
        $sql = sprintf(
            "INSERT INTO artifact_rule (group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id) VALUES (%s, %s, %s, %s, %s, %s)",
            $this->da->quoteSmart($group_artifact_id),
            $this->da->quoteSmart($source_field_id),
            $this->da->quoteSmart($source_value_id),
            $this->da->quoteSmart($target_field_id),
            $this->da->quoteSmart($rule_type),
            $this->da->quoteSmart($target_value_id)
        );
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }


    /**
    * Searches ArtifactRule by GroupArtifactId
    * @return DataAccessResult
    */
    public function searchByGroupArtifactIdWithOrder($groupArtifactId)
    {
        $sql = sprintf(
            "SELECT ar.id, ar.source_field_id, ar.source_value_id, ar.target_field_id, ar.rule_type, ar.target_value_id " .
                       " FROM artifact_rule AS ar " .
                       "   INNER JOIN artifact_field_usage AS afu1 ON (ar.source_field_id = afu1.field_id AND ar.group_artifact_id = afu1.group_artifact_id) " .
                       "   INNER JOIN artifact_field_usage AS afu2 ON (ar.target_field_id = afu2.field_id AND ar.group_artifact_id = afu2.group_artifact_id) " .
                       "   LEFT JOIN artifact_field_value_list AS afvls " .
                       "      ON (ar.source_field_id = afvls.field_id AND ar.group_artifact_id = afvls.group_artifact_id AND ar.source_value_id = afvls.value_id) " .
                       "   LEFT JOIN artifact_field_value_list AS afvlt " .
                       "      ON (ar.target_field_id = afvlt.field_id AND ar.group_artifact_id = afvlt.group_artifact_id AND ar.target_value_id = afvlt.value_id) " .
                       " WHERE ar.group_artifact_id = %s " .
                       " ORDER BY afu1.place, afu2.place, afvls.order_id, afvlt.order_id, ar.id",
            $this->da->quoteSmart($groupArtifactId)
        );
        return $this->retrieve($sql);
    }

    public function deleteById($id)
    {
        $sql = sprintf(
            "DELETE FROM artifact_rule WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function deleteRuleState($group_artifact_id, $source, $source_value, $target, $rule_types)
    {
        $quoted_types = array();
        foreach ($rule_types as $type) {
            $quoted_types[] = $this->da->quoteSmart($type);
        }
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id = %s ' .
                       '   AND source_field_id   = %s ' .
                       '   AND source_value_id   = %s ' .
                       '   AND target_field_id   = %s ' .
                       '   AND rule_type IN (%s) ',
            $this->da->quoteSmart($group_artifact_id),
            $this->da->quoteSmart($source),
            $this->da->quoteSmart($source_value),
            $this->da->quoteSmart($target),
            implode(', ', $quoted_types)
        );
        return $this->retrieve($sql);
    }

    public function deleteByGroupArtifactIdAndSourceAndSourceValueAndTargetAndRuleType($artifact_type, $source, $source_value, $target, $rule_type)
    {
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id = %s ' .
                       '   AND source_field_id   = %s ' .
                       '   AND source_value_id   = %s ' .
                       '   AND target_field_id   = %s ' .
                       '   AND rule_type         = %s ',
            $this->da->quoteSmart($artifact_type),
            $this->da->quoteSmart($source),
            $this->da->quoteSmart($source_value),
            $this->da->quoteSmart($target),
            $this->da->quoteSmart($rule_type)
        );
        return $this->update($sql);
    }

    public function deleteByGroupArtifactIdAndSourceAndTargetAndTargetValueAndRuleType($artifact_type, $source, $target, $target_value, $rule_type)
    {
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id = %s ' .
                       '   AND source_field_id   = %s ' .
                       '   AND target_field_id   = %s ' .
                       '   AND target_value_id   = %s ' .
                       '   AND rule_type         = %s ',
            $this->da->quoteSmart($artifact_type),
            $this->da->quoteSmart($source),
            $this->da->quoteSmart($target),
            $this->da->quoteSmart($target_value),
            $this->da->quoteSmart($rule_type)
        );
        return $this->update($sql);
    }

    public function deleteRulesByGroupArtifactId($artifact_type)
    {
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id = %s ',
            $this->da->quoteSmart($artifact_type)
        );
        return $this->update($sql);
    }
    public function deleteByField($artifact_type, $field_id)
    {
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id = %s ' .
                       '   AND (source_field_id  = %s ' .
                       '   OR target_field_id    = %s) ',
            $this->da->quoteSmart($artifact_type),
            $this->da->quoteSmart($field_id),
            $this->da->quoteSmart($field_id)
        );
        return $this->update($sql);
    }
    public function deleteByFieldValue($artifact_type, $field_id, $value_id)
    {
        $sql = sprintf(
            'DELETE FROM artifact_rule ' .
                       ' WHERE group_artifact_id   = %s ' .
                       '   AND ( ' .
                       '     ( source_field_id     = %s ' .
                       '       AND source_value_id = %s ' .
                       '     )  ' .
                       '     OR ' .
                       '     ( target_field_id     = %s ' .
                       '       AND target_value_id = %s ' .
                       '     ) ' .
                       '   ) ',
            $this->da->quoteSmart($artifact_type),
            $this->da->quoteSmart($field_id),
            $this->da->quoteSmart($value_id),
            $this->da->quoteSmart($field_id),
            $this->da->quoteSmart($value_id)
        );
        return $this->update($sql);
    }
    public function copyRules($from_artifact_type, $to_artifact_type)
    {
        $sql = sprintf(
            'INSERT INTO artifact_rule (group_artifact_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id) ' .
                        ' SELECT %s, source_field_id, source_value_id, target_field_id, rule_type, target_value_id ' .
                        ' FROM artifact_rule ' .
                        ' WHERE group_artifact_id = %s ',
            $this->da->quoteSmart($to_artifact_type),
            $this->da->quoteSmart($from_artifact_type)
        );
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($dar === false) {
                return false;
            }
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }
}
