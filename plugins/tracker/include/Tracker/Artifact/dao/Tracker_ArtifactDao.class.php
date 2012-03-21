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

class Tracker_ArtifactDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_artifact';
    }
    
    function searchById($id) {
        $id      = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }
    
    function searchByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id ";
        return $this->retrieve($sql);
    }
    
    /**
     * @param string $artifact_ids "2,14,15"
     */
    function searchLastChangesetIds($artifact_ids, $ugroups) {
        $artifact_ids = explode(',', $artifact_ids); // array(2, 14, 15);
        $artifact_ids = array_map(array($this->da, 'escapeInt'), $artifact_ids); // array(2, 14, 15)
        $artifact_ids = implode(',', $artifact_ids); // 2,14,15
        $sql = " SELECT tracker_id, GROUP_CONCAT(id) AS id, GROUP_CONCAT(last_changeset_id) AS last_changeset_id";
        $from = " FROM $this->table_name AS artifact";
        $where = " WHERE id IN (" .$artifact_ids. ")";
        $group = " GROUP BY tracker_id";
                
        $user = UserManager::instance()->getCurrentuser();
        if (!$user->isSuperUser()) {
            $from   .= " LEFT JOIN permissions ON (permissions.object_id = CAST(artifact.id AS CHAR) AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS')";
            $where  .= " AND (artifact.use_artifact_permissions = 0 OR  (permissions.ugroup_id IN (". $ugroups.")))";
        }
        
        $sql .= $from.$where.$group;
        
        return $this->retrieve($sql);
    }
    
    public function searchOpenByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id) 
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS 
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                WHERE (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR 
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                ORDER BY A.id DESC";
        //TODO: Scalability
        return $this->retrieve($sql);
    }
    
    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * submitted by user $user_id in all projects and trackers.
     * 
     * @param int $user_id the ID of the user
     *
     * @return DataAccessResult The result of the query
     */
    function searchOpenSubmittedByUserId($user_id) {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT A.id AS id, A.tracker_id, A.use_artifact_permissions, C.id AS changeset_id, CVT.value AS title, A.submitted_by, A.submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id) 
                    INNER JOIN groups AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS 
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)          
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE A.submitted_by = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR 
                        CVL2.bindvalue_id = SS.open_value_id
                     )
               ORDER BY G.group_name ASC, T.id ASC, A.id DESC";
        return $this->retrieve($sql);
    }
    
    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * assigned to user $user_id in all projects and trackers (see tracker semantic for a definition of assigned to).
     * 
     * @param int $user_id the ID of the user
     *
     * @return DataAccessResult The result of the query
     */
    function searchOpenAssignedToUserId($user_id) {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT A.id AS id, A.tracker_id, A.use_artifact_permissions, C.id AS changeset_id, CVT.value AS title, A.submitted_by, A.submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id) 
                    INNER JOIN groups AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS 
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    
                    INNER JOIN (                                                                -- Look if there is any contibutor semantic defined
                        tracker_semantic_contributor as SC 
                        INNER JOIN tracker_changeset_value AS CV1 ON (SC.field_id = CV1.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV1.id = CVL.changeset_value_id)
                    ) ON (T.id = SC.tracker_id AND C.id = CV1.changeset_id)
                    
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE CVL.bindvalue_id = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR 
                        CVL2.bindvalue_id = SS.open_value_id
                     )
               ORDER BY G.group_name ASC, T.id ASC, A.id DESC";
        return $this->retrieve($sql);
    }
    
    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * submitted by or assigned to user $user_id in all projects and trackers (see tracker semantic for a definition of assigned to).
     * 
     * @param int $user_id the ID of the user
     *
     * @return DataAccessResult The result of the query
     */
    function searchOpenSubmittedByOrAssignedToUserId($user_id) {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT A.id AS id, A.tracker_id, A.use_artifact_permissions, C.id AS changeset_id, CVT.value AS title, A.submitted_by, A.submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id) 
                    INNER JOIN groups AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS 
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    
                    INNER JOIN (                                                                -- Look if there is any contibutor semantic defined
                        tracker_semantic_contributor as SC 
                        INNER JOIN tracker_changeset_value AS CV1 ON (SC.field_id = CV1.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CV1.id = CVL.changeset_value_id)
                    ) ON (T.id = SC.tracker_id AND C.id = CV1.changeset_id)
                    
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE (A.submitted_by = $user_id
                        OR 
                       CVL.bindvalue_id = $user_id)
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR 
                        CVL2.bindvalue_id = SS.open_value_id
                     )
               ORDER BY G.group_name ASC, T.id ASC, A.id DESC";
        return $this->retrieve($sql);
    }
        
    function searchStatsForTracker($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT R1.t AS last_creation, R2.t AS last_update, R3.t as nb_total, R4.t as nb_open
                FROM (SELECT MAX(a.submitted_on) AS t
                    FROM tracker_artifact AS a
                    WHERE a.tracker_id = $tracker_id) AS R1,
                    
                    (SELECT MAX(c.submitted_on) AS t
                    FROM tracker_artifact AS b INNER JOIN tracker_changeset AS c ON (b.last_changeset_id = c.id)
                    WHERE b.tracker_id = $tracker_id) AS R2,
                    
                    (SELECT count(*) as t
                    FROM tracker_artifact
                    WHERE tracker_id = $tracker_id) AS R3,
                    
                    (SELECT count(distinct(a.id)) AS t 
                    FROM tracker_artifact AS a 
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND a.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id AND ss.open_value_id = cvl.bindvalue_id)
                    WHERE a.tracker_id = $tracker_id) AS R4
                ";
        return $this->retrieve($sql);
    }
    
    
    function quote_keyword($keyword) {
        return $this->da->quoteSmart('%'. $keyword .'%');
    }
    function searchByKeywords($tracker_id, $keywords, $criteria, $offset, $limit) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $criteria   = $criteria === 'OR' ? 'OR' : 'AND'; //make sure that the request is not forged
        $offset     = $this->da->escapeInt($offset);
        $limit      = $this->da->escapeInt($limit);
        $keywords_array = array_map(array($this, 'quote_keyword'), explode(" ", $keywords));
        
        // search in all text fields
        $search_query1 = implode($keywords_array, " $criteria cvt.value LIKE ");
        $search_query2 = implode($keywords_array, " $criteria cc.body LIKE ");
        $sql = "SELECT SQL_CALC_FOUND_ROWS a.id AS artifact_id 
                FROM tracker_artifact AS a 
                INNER JOIN tracker_changeset AS c ON (a.id = c.artifact_id) 
                INNER JOIN tracker_changeset_value AS cv ON (c.id = cv.changeset_id) 
                INNER JOIN tracker_changeset_value_text AS cvt ON (cv.id = cvt.changeset_value_id)
                LEFT JOIN tracker_changeset_comment AS cc ON (c.id = cc.changeset_id) 
                WHERE a.tracker_id = $tracker_id AND 
                      (
                        (cvt.value LIKE $search_query1) OR
                        (cc.body LIKE $search_query2)
                      )
                GROUP BY a.submitted_on DESC 
                LIMIT $offset, $limit";
        return $this->retrieve($sql);
    }
    
    function create($tracker_id, $submitted_by, $use_artifact_permissions) {
        $tracker_id               = $this->da->escapeInt($tracker_id);
        $use_artifact_permissions = $this->da->escapeInt($use_artifact_permissions);
        $submitted_on             = $this->da->escapeInt($_SERVER['REQUEST_TIME']);
        $submitted_by             = $this->da->escapeInt($submitted_by);
        $id_sharing = new TrackerIdSharingDao();
        if ($id = $id_sharing->generateArtifactId()) {
            $sql = "INSERT INTO $this->table_name 
                    (id, tracker_id, submitted_by, submitted_on, use_artifact_permissions)
                    VALUES ($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)";
            return $this->updateAndGetLastId($sql);
        }
        return false;
    }
    
    function save($id, $tracker_id, $use_artifact_permissions) {
        $id                       = $this->da->escapeInt($id);
        $tracker_id               = $this->da->escapeInt($tracker_id);
        $use_artifact_permissions = $this->da->escapeInt($use_artifact_permissions);
        
        $sql = "UPDATE $this->table_name SET 
                   tracker_id               = $tracker_id,
                   use_artifact_permissions = $use_artifact_permissions
                WHERE id = $id ";
        return $this->update($sql);
    }
    
    function delete($id) {
        $sql = "DELETE FROM $this->table_name WHERE id = ". $this->da->escapeInt($id);
        return $this->update($sql);
    }
}
?>
