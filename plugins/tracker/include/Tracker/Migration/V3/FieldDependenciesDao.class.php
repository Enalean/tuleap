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

class Tracker_Migration_V3_FieldDependenciesDao extends DataAccessObject {
    
    public function addDependencies($tv3_id, $tv5_id) {
        $this->sourceAndTargetAreStatic($tv3_id, $tv5_id);
        $this->sourceIsUserAndTargetIsStatic($tv3_id, $tv5_id);
        $this->sourceIsStaticAndTargetIsUser($tv3_id, $tv5_id);
        $this->sourceAndTargetAreUser($tv3_id, $tv5_id);
    }

    private function sourceAndTargetAreStatic($tv3_id, $tv5_id) {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT NULL, $tv5_id, sf.id, sbv.id, tf.id, r.rule_type, tbv.id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND sf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_static_value AS sbv ON(sbv.field_id = sf.id AND r.source_value_id = sbv.old_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND tf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_static_value AS tbv ON(tbv.field_id = tf.id AND r.target_value_id = tbv.old_id)
                WHERE r.group_artifact_id = $tv3_id";
        return $this->update($sql);
    }

    private function sourceIsUserAndTargetIsStatic($tv3_id, $tv5_id) {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT NULL, $tv5_id, sf.id, r.source_value_id, tf.id, r.rule_type, tbv.id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND sf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_users AS sfu ON(sf.id = sfu.field_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND tf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_static_value AS tbv ON(tbv.field_id = tf.id AND r.target_value_id = tbv.old_id)
                WHERE r.group_artifact_id = $tv3_id";
        return $this->update($sql);
    }

    private function sourceIsStaticAndTargetIsUser($tv3_id, $tv5_id) {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT NULL, $tv5_id, sf.id, sbv.id, tf.id, r.rule_type, r.target_value_id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND sf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_static_value AS sbv ON(sbv.field_id = sf.id AND r.source_value_id = sbv.old_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND tf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_users AS tfu ON(tf.id = tfu.field_id)
                WHERE r.group_artifact_id = $tv3_id";
        return $this->update($sql);
    }

    private function sourceAndTargetAreUser($tv3_id, $tv5_id) {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT NULL, $tv5_id, sf.id, r.source_value_id, tf.id, r.rule_type, r.target_value_id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND sf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_users AS sfu ON(sf.id = sfu.field_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND tf.tracker_id = $tv5_id)
                    INNER JOIN tracker_field_list_bind_users AS tfu ON(tf.id = tfu.field_id)
                WHERE r.group_artifact_id = $tv3_id";
        return $this->update($sql);
    }
}

?>
