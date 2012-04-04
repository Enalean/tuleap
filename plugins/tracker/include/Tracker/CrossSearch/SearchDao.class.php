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

require_once 'common/dao/include/DataAccessObject.class.php';
require_once 'SharedField.class.php';

class Tracker_CrossSearch_SearchDao extends DataAccessObject {
    
    public function searchMatchingArtifacts(array $tracker_ids, array $shared_fields, array $semantic_fields, array $excluded_artifact_ids = array()) {
        $tracker_ids               = $this->da->quoteSmartImplode(',', $tracker_ids);
        $excluded_artifact_ids     = $this->da->quoteSmartImplode(',', $excluded_artifact_ids);
        $shared_fields_constraints = $this->getSharedFieldsSqlFragment($shared_fields);
        $title_constraint          = $this->getTitleSqlFragment($semantic_fields['title']);
        $status_constraint         = $this->getStatusSqlFragment($semantic_fields['status']);
        
        $sql = "
            SELECT artifact.id,
                   artifact.last_changeset_id,
                   CVT.value                      AS title,
                   artifact.tracker_id,
                   GROUP_CONCAT(CVAL.artifact_id) AS artifactlinks
                   
            FROM       tracker_artifact  AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            
            $shared_fields_constraints
            
            LEFT JOIN (
                           tracker_changeset_value      AS CV
                INNER JOIN tracker_semantic_title       AS ST  ON (CV.field_id = ST.field_id)
                INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id       = CVT.changeset_value_id)
            
            ) ON (c.id = CV.changeset_id)
            
            LEFT JOIN (
                           tracker_changeset_value      AS CV3
                INNER JOIN tracker_semantic_status      AS SS  ON (CV3.field_id = SS.field_id)
                INNER JOIN tracker_changeset_value_list AS CVL ON (CV3.id       = CVL.changeset_value_id AND SS.open_value_id = CVL.bindvalue_id)
            
            ) ON (c.id = CV3.changeset_id)

            LEFT JOIN (
                           tracker_changeset_value_artifactlink AS CVAL
                INNER JOIN tracker_changeset_value              AS CV2 ON (CV2.id = CVAL.changeset_value_id) 
            
            ) ON CV2.changeset_id = artifact.last_changeset_id

            WHERE artifact.use_artifact_permissions = 0
            AND   artifact.tracker_id IN ($tracker_ids)
            $title_constraint
            $status_constraint
        ";
        
        if ($excluded_artifact_ids != '') {
            $sql .= "
              AND artifact.id NOT IN ($excluded_artifact_ids) ";
        }
        $sql .= "
            GROUP BY artifact.id
            ORDER BY title
        ";
        return $this->retrieve($sql);
    }
    
    protected function getSharedFieldsSqlFragment(array $shared_fields) {
        $fragment_number = 0;
        $sql_fragments   = array();
        
        foreach ($shared_fields as $shared_field) {
            $sql_fragments[] = $this->getSharedFieldFragment($fragment_number++, $shared_field);
        }
        
        return implode(' ', $sql_fragments);
    }
    
    protected function getSharedFieldFragment($fragment_number, Tracker_CrossSearch_SharedField $shared_field) {
        $field_ids = $this->da->quoteSmartImplode(',', $shared_field->getFieldIds());
        $value_ids = $this->da->quoteSmartImplode(',', $shared_field->getValueIds());
        
        // Table aliases
        $changeset_value      = "CV_$fragment_number";
        $changeset_value_list = "CVL_$fragment_number";
        
        $sql_fragment = "
            INNER JOIN tracker_changeset_value AS $changeset_value ON (
                    $changeset_value.changeset_id = c.id
                AND $changeset_value.field_id IN ($field_ids)
            )
            INNER JOIN tracker_changeset_value_list AS $changeset_value_list ON (
                    $changeset_value_list.changeset_value_id = $changeset_value.id
                AND $changeset_value_list.bindvalue_id IN ($value_ids)
            )
        ";
        
        return $sql_fragment;
    }
    
    protected function getTitleSqlFragment($title) {
        $title = $this->da->quoteSmart($title);
        if (! $title) { return ''; }
        
        return "AND CVT.value LIKE CONCAT('%', $title, '%')";
    }
    
    protected function getStatusSqlFragment($status) {
        switch ($status) {
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN:
            return "AND  SS.open_value_id IS NOT NULL";
            break;
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_CLOSED:
            return "AND  SS.open_value_id IS NULL";    
            break;
        default:
            // no constraint
        }
    }
}    
?>
