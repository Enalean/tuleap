<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for Tracker_Rule
 */
class Tracker_Rule_Date_Dao extends DataAccessObject {

    public function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_rule_date';
    }

    /**
     * Searches Tracker_Rule by Id
     * @return DataAccessResult
     */
    function searchById($id) {
        $sql = sprintf("SELECT *
                        FROM tracker_rule_date 
                            JOIN tracker_rule
                            ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                        WHERE tracker_rule.id = %s",
				$this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
     * Searches Tracker_Rule by TrackerId
     * @return DataAccessResult
     */
    function searchByTrackerId($tracker_id) {
        $sql = sprintf("SELECT *
                        FROM tracker_rule 
                            JOIN tracker_rule_date
                            ON (tracker_rule.id = tracker_rule_list.tracker_rule_id)
                        WHERE tracker_rule.tracker_id = %s",
				$this->da->quoteSmart($tracker_id));
        return $this->retrieve($sql);
    }

    /**
     * 
     * @param Tracker_Rule_Date $rule
     */
    public function save(Tracker_Rule_Date $rule) {
        
        $sql_insert_rule = sprintf("INSERT INTO tracker_rule (tracker_id, rule_type)
                            VALUES (%s, %s)",
                            $this->da->quoteSmart($rule->getTracker()->getId()),
                            $this->da->quoteSmart(Tracker_Rule::RULETYPE_DATE)
                           );

        $this->update($sql_insert_rule);
        $tracker_rule_id = $this->da->lastInsertId();

        $sql = sprintf("INSERT INTO tracker_rule_date (tracker_rule_id, source_field_id, source_value_id, target_field_id, target_value_id)
                        VALUES (%s, %s, %s, %s, %s)",
                        $tracker_rule_id,
                        $this->da->quoteSmart($rule->getSourceField()->getId()),
                        $this->da->quoteSmart($rule->getTargetField()->getId()));
        $this->retrieve($sql);
    }
}
?>