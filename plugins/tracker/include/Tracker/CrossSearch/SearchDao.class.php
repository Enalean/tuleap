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
    
    public function searchMatchingArtifacts(Tracker_CrossSearch_Query $query,
                                            array $tracker_ids, 
                                            array $shared_fields, 
                                            array $semantic_fields, 
                                            array $artifact_link_field_ids_for_column_display, 
                                            array $excluded_artifact_ids = array()) {
        $tracker_ids               = $this->da->quoteSmartImplode(',', $tracker_ids);
        $excluded_artifact_ids     = $this->da->quoteSmartImplode(',', $excluded_artifact_ids);
        $shared_fields_constraints = $this->getSharedFieldsSqlFragment($shared_fields);
        $title_constraint          = $this->getTitleSqlFragment($semantic_fields['title']);
        $status_constraint         = $this->getStatusSqlFragment($semantic_fields['status']);
        $tracker_constraint        = $tracker_ids ? " AND   artifact.tracker_id IN ($tracker_ids) " : "";
        $artifact_link_constraints = $this->getArtifactLinkSearchSqlFragment($query->listArtifactIds());
        
        // TODO /!\ GROUP CONCAT returns each select value twice, to be checked (temp. solved by a array_unique in
        // view
        $artifact_link_columns_select = $this->getArtifactLinkSelects($artifact_link_field_ids_for_column_display);
        $artifact_link_columns_join   = $this->getArtifactLinkColumns($artifact_link_field_ids_for_column_display);
        
        $sql = "
            SELECT artifact.id,
                   artifact.last_changeset_id,
                   CVT.value                      AS title,
                   artifact.tracker_id,
                   GROUP_CONCAT(CVAL.artifact_id) AS artifactlinks
                   $artifact_link_columns_select
                   
            FROM       tracker_artifact  AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            
            $shared_fields_constraints
            
            $artifact_link_constraints
        
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

            $artifact_link_columns_join
        
            WHERE artifact.use_artifact_permissions = 0
            $tracker_constraint
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
        //echo "<pre>$sql</pre>";
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
        $title = trim($title); 
        if (! $title) {
            return '';
        }
        $title = $this->da->quoteSmart('%'. $title .'%');
        return " AND CVT.value LIKE $title ";
    }
    
    protected function getStatusSqlFragment($status) {
        switch ($status) {
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN:
            return " AND SS.open_value_id IS NOT NULL ";
            break;
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_CLOSED:
            return " AND SS.open_value_id IS NULL ";
            break;
        default:
            // no constraint
        }
    }
    
    /**
     * Return the SQL statements that perform "artifact link" search
     * 
     * @param array $artifact_ids
     * 
     * @return String
     */
    protected function getArtifactLinkSearchSqlFragment(array $artifact_ids) {
        if (count($artifact_ids)) {
            $artifact_ids_list = $this->da->quoteSmartImplode(',', $artifact_ids);
            $field_ids_list    = $this->getArtifactLinkFields($artifact_ids_list);
            if ($field_ids_list) {
                return $this->getSearchOnArtifactLink($field_ids_list, $artifact_ids_list);
            }
        }
        return '';
    }
    
    /**
     * Build the search for artifact link
     * 
     * Looks for artifacts that are linked to a given one:
     * - Sprint #34 "2.0"
     *   (links: story #20, story #30, task #40)
     * 
     * Given I Look for sprint #34 related artifacts it returns stories 20, 30 and 40
     * 
     * @param String  $field_ids_list
     * @param String  $artifact_ids_list
     * 
     * @return String 
     */
    protected function getSearchOnArtifactLink($field_ids_list, $artifact_ids_list) {        
        // Table aliases
        $tracker_artifact                     = 'ALS_A';
        $tracker_changeset_value              = 'ALS_CV';
        $tracker_changeset_value_artifactlink = 'ALS_CVAL';
        
        $sql = "INNER JOIN tracker_artifact                    AS $tracker_artifact          ON ($tracker_artifact.id IN ($artifact_ids_list))
                INNER JOIN tracker_changeset_value             AS $tracker_changeset_value   ON ($tracker_artifact.last_changeset_id = $tracker_changeset_value.changeset_id 
                                                                                                 AND $tracker_changeset_value.field_id IN ($field_ids_list))
                INNER JOIN tracker_changeset_value_artifactlink AS $tracker_changeset_value_artifactlink ON (artifact.id = $tracker_changeset_value_artifactlink.artifact_id
                                                                                                             AND $tracker_changeset_value.id = $tracker_changeset_value_artifactlink.changeset_value_id)";
        return $sql;
    }
    
    /**
     * Find artifact link fields used by given artifacts
     * 
     * @param String $artifact_ids_list
     * 
     * @return String 
     */
    protected function getArtifactLinkFields($artifact_ids_list) {
        $sql = "SELECT GROUP_CONCAT(DISTINCT F.id) AS field_ids
                FROM tracker_field            AS F
                  INNER JOIN tracker          AS T ON (F.tracker_id = T.id)
                  INNER JOIN tracker_artifact AS A ON (T.id = A.tracker_id)
                WHERE A.id IN ($artifact_ids_list)
                  AND formElement_type = 'art_link'";
        $dar = $this->retrieve($sql);
        if ($dar && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['field_ids'];
        } else {
            return '';
        }
    }
    
    /**
     * Return the columns to display artifact link titles
     * 
     * @param array $field_ids
     * 
     * @return String
     */
    protected function getArtifactLinkSelects(array $field_ids) {
        $sql = '';
        foreach ($field_ids as $field_id) {
            $sql .= ', GROUP_CONCAT(AL_COL_'.$field_id.'.id) AS art_link_'.$field_id;
        }
        return $sql;
    }

    /**
     * Return the join statements to retrieve artifact link titles
     * 
     * @param array $field_ids
     * 
     * @return String
     */
    protected function getArtifactLinkColumns(array $field_ids) {
        $sql = '';
        foreach ($field_ids as $field_id) {
            $sql .= $this->getArtifactLinkColumn($field_id);
        }
        return $sql;
    }
    
    /**
     * Return the needed joins to retrieve artifact links title
     * 
     * @param Integer $field_id
     * 
     * @return String
     */
    protected function getArtifactLinkColumn($field_id) {
        $field_id = intval($field_id);
        
        $tracker_artifact_title        = 'AL_COL_'.$field_id;
        $al_tracker_changeset_value    = 'AL_COL_CV_'.$field_id;
        $al_tracker_changeset_value_al = 'AL_COL_CVAL_'.$field_id;
                
        $sql = "LEFT JOIN (
                    tracker_artifact AS $tracker_artifact_title

                INNER JOIN tracker_changeset_value  AS $al_tracker_changeset_value   ON ($tracker_artifact_title.last_changeset_id = $al_tracker_changeset_value.changeset_id AND $al_tracker_changeset_value.field_id IN ($field_id))
                INNER JOIN tracker_changeset_value_artifactlink AS $al_tracker_changeset_value_al ON ($al_tracker_changeset_value.id = $al_tracker_changeset_value_al.changeset_value_id)
                ) ON ($al_tracker_changeset_value_al.artifact_id = artifact.id)";
        return $sql;
    }

}
?>
