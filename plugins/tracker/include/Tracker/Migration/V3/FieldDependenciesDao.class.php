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
    
    public function addDependencies() {
        /*$this->sourceAndTargetAreStatic();
        $this->sourceIsUserAndTargetIsStatic();
        $this->sourceIsStaticAndTargetIsUser();
        $this->sourceAndTargetAreUser();*/
    }

    private function sourceAndTargetAreStatic() {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT r.id, r.group_artifact_id, sf.id, sbv.id, tf.id, r.rule_type, tbv.id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND r.group_artifact_id = sf.tracker_id)
                    INNER JOIN tracker_field_list_bind_static_value AS sbv ON(sbv.field_id = sf.id AND r.source_value_id = sbv.old_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND r.group_artifact_id = tf.tracker_id)
                    INNER JOIN tracker_field_list_bind_static_value AS tbv ON(tbv.field_id = tf.id AND r.target_value_id = tbv.old_id)";
    }

    private function sourceIsUserAndTargetIsStatic() {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT r.id, r.group_artifact_id, sf.id, r.source_value_id, tf.id, r.rule_type, tbv.id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND r.group_artifact_id = sf.tracker_id)
                    INNER JOIN tracker_field_list_bind_users AS sfu ON(sf.id = sfu.field_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND r.group_artifact_id = tf.tracker_id)
                    INNER JOIN tracker_field_list_bind_static_value AS tbv ON(tbv.field_id = tf.id AND r.target_value_id = tbv.old_id)";
    }

    private function sourceIsStaticAndTargetIsUser() {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT r.id, r.group_artifact_id, sf.id, sbv.id, tf.id, r.rule_type, r.target_value_id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND r.group_artifact_id = sf.tracker_id)
                    INNER JOIN tracker_field_list_bind_static_value AS sbv ON(sbv.field_id = sf.id AND r.source_value_id = sbv.old_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND r.group_artifact_id = tf.tracker_id)
                    INNER JOIN tracker_field_list_bind_users AS tfu ON(tf.id = tfu.field_id)";
    }

    private function sourceAndTargetAreUser() {
        $sql = "INSERT INTO tracker_rule(id, tracker_id, source_field_id, source_value_id, target_field_id, rule_type, target_value_id)
                SELECT r.id, r.group_artifact_id, sf.id, r.source_value_id, tf.id, r.rule_type, r.target_value_id
                FROM artifact_rule AS r
                    INNER JOIN tracker_field AS sf ON(r.source_field_id = sf.old_id AND r.group_artifact_id = sf.tracker_id)
                    INNER JOIN tracker_field_list_bind_users AS sfu ON(sf.id = sfu.field_id)
                    INNER JOIN tracker_field AS tf ON(r.target_field_id = tf.old_id AND r.group_artifact_id = tf.tracker_id)
                    INNER JOIN tracker_field_list_bind_users AS tfu ON(tf.id = tfu.field_id)";
    }
}

?>
