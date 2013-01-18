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

class Tracker_CrossSearch_SearchDao extends DataAccessObject {
    
    /**
     * Monstro query 
     * 
     * @param User $user
     * @param unknown_type $group_id
     * @param Tracker_CrossSearch_Query $query
     * @param array $tracker_ids
     * @param array $shared_fields
     * @param array $semantic_fields
     * @param array $artifact_link_field_ids_for_column_display
     * @param array $excluded_artifact_ids
     */
    public function searchMatchingArtifacts(User $user,
                                            $group_id,
                                            Tracker_CrossSearch_Query $query,
                                            array $tracker_ids, 
                                            array $shared_fields, 
                                            array $semantic_fields, 
                                            array $artifact_link_field_ids_for_column_display, 
                                            array $excluded_artifact_ids = array()) {
        $report_dao = new Tracker_ReportDao();
        $report_dao->logStart(__METHOD__, json_encode(array(
            'user'     => $user->getUserName(),
            'project'  => $group_id, 
            'query'    => $query->toArrayOfDoom(),
            'trackers' => array_values($tracker_ids)
        )));
        
        $is_super_user                = $user->isSuperUser();
        $ugroups                      = $user->getUgroups($group_id, array());
        $quoted_ugroups               = $this->da->quoteSmartImplode(',', $ugroups);
        
        $quoted_tracker_ids           = $this->da->quoteSmartImplode(',', $tracker_ids);
        $excluded_artifact_ids        = $this->da->quoteSmartImplode(',', $excluded_artifact_ids);
        
        $shared_fields_constraints    = $this->getSharedFieldsSqlFragment($shared_fields);
        $title_constraint             = $this->getTitleSqlFragment($this->getSemanticFieldCriteria($semantic_fields, 'title'));
        $status_constraint            = $this->getStatusSqlFragment($this->getSemanticFieldCriteria($semantic_fields, 'status'));
        $tracker_constraint           = $tracker_ids ? " AND   artifact.tracker_id IN ($quoted_tracker_ids) " : "";
        
        $artifact_ids_list            = $query->listArtifactIds();
        $artifact_link_constraints    = '';
        if (count($artifact_ids_list)) {
            $artifact_ids_list            = $this->da->quoteSmartImplode(',', $artifact_ids_list);
            $artifacts_fields             = $this->getArtifactLinkFields($artifact_ids_list, $is_super_user, $quoted_ugroups);
            $artifact_link_constraints    = $this->getArtifactLinkSearchSqlFragment($artifacts_fields);
        }
        
        $artifact_link_columns_select = $this->getArtifactLinkSelects($artifact_link_field_ids_for_column_display);
        $artifact_link_columns_join   = $this->getArtifactLinkColumns($artifact_link_field_ids_for_column_display, $is_super_user, $quoted_ugroups);
        
        $artifact_permissions = $report_dao->getSqlFragmentForArtifactPermissions($user->isSuperUser(), $user->getUgroups($group_id, array()));
        $artifact_permissions_join  = $artifact_permissions['from'];
        $artifact_permissions_where = $artifact_permissions['where'];
        
        $tracker_semantic_title_join  = $this->getTrackerSemanticTitleJoin($is_super_user, $quoted_ugroups);
        $tracker_semantic_status_join = $this->getTrackerSemanticStatusJoin($is_super_user, $quoted_ugroups);

        $from  = " FROM tracker_artifact AS artifact
                   INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id) 
                   $shared_fields_constraints 
                   $artifact_link_constraints 
                   $tracker_semantic_title_join 
                   $tracker_semantic_status_join 
                   $artifact_permissions_join ";
        $where = " WHERE 1 $artifact_permissions_where $title_constraint $status_constraint ";

        $permissions_manager = PermissionsManager::instance();
        $tracker_factory     = TrackerFactory::instance();
        $sqls = array();
        foreach ($tracker_ids as $tracker_id) {
            // {{{ This is a big copy 'n paste from Tracker_Report::getMatchingIdsInDb.
            //     TODO:
            //          instead of building a big query with plenty of unions, 
            //          call getMatchingIdsInDb foreach tracker involved in the
            //          crosssearch query. As getMatchingIdsInDb returns the
            //          tuple (artifact_ids, matching_ids) -- where ids are 
            //          comma separated --, we will have to do the join in php 
            //          by concatenating strings.
            //          Example:
            //              $artifact_ids = $changeset_ids = '';
            //              foreach ($trackers as $tracker) {
            //                  merge($artifact_ids, $changeset_ids, report->getMatchingIdsInDb(..., $tracker, ...));
            //              }
            //              crosssearch->retrieveColumns($artifact_ids, $changeset_ids)
            //
            //          And if we are feeling lucky, we can also move the 
            //              reportdao->searchMatchingIds in searchdao since it make more sense.
            //
            //          Possible caveat:
            //              - 1 big sql query full of unions is really slower  
            //                than n small queries?
            $instances            = array('artifact_type' => $tracker_id);
            $ugroups              = $user->getUgroups($group_id, $instances);
            $static_ugroups       = $user->getStaticUgroups($group_id);
            $dynamic_ugroups      = $user->getDynamicUgroups($group_id, $instances);
            $permissions          = $permissions_manager->getPermissionsAndUgroupsByObjectid($tracker_id, $ugroups);
            $subwhere             = " $where AND artifact.tracker_id = $tracker_id ";
            $tracker              = $tracker_factory->getTrackerById($tracker_id);
            $contributor_field    = $tracker->getContributorField();
            $contributor_field_id = $contributor_field ? $contributor_field->getId() : null;
            // }}}
            $sqls = array_merge(
                $sqls, 
                $report_dao->getSqlFragmentsAccordinglyToTrackerPermissions($user->isSuperUser(), $from, $subwhere, $group_id, $tracker_id, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id)
            );
        }
        array_filter($sqls);

        if (count($sqls) == 0) {
            $results = new DataAccessResultEmpty();
        } else {
            $union = implode(' UNION ', $sqls);

            $sql = "SET SESSION group_concat_max_len = 134217728";
            $this->retrieve($sql);

            $sql = "
            SELECT artifact.id,
                   artifact.last_changeset_id,
                   CVT.value                                AS title,
                   artifact.tracker_id,
                   GROUP_CONCAT(CVAL.artifact_id) AS artifactlinks
                   $artifact_link_columns_select
                   
            FROM       tracker_artifact  AS artifact
            INNER JOIN tracker_artifact_priority ON (tracker_artifact_priority.curr_id = artifact.id)
            INNER JOIN ( $union ) AS R ON (R.id = artifact.id)
            INNER JOIN tracker_changeset AS c ON (R.last_changeset_id = c.id)

            -- shared_fields_constraints
            
            -- artifact_link_constraints

            $tracker_semantic_title_join

            LEFT JOIN (
                           tracker_changeset_value_artifactlink AS CVAL
                INNER JOIN tracker_changeset_value              AS CV2 ON (CV2.id = CVAL.changeset_value_id) 
            
            ) ON CV2.changeset_id = artifact.last_changeset_id

            $artifact_link_columns_join
            -- artifact_permissions_join
        
            WHERE 1
            -- artifact_permissions_where
                    $tracker_constraint
            -- title_constraint
            -- status_constraint
            ";
        
            if ($excluded_artifact_ids != '') {
                $sql .= "
                  AND artifact.id NOT IN ($excluded_artifact_ids) ";
            }
            $sql .= "
                GROUP BY artifact.id
                ORDER BY tracker_artifact_priority.rank
            ";
            $results = $this->retrieve($sql);
        }
        $nb_matching = count($results);
        $report_dao->logEnd(__METHOD__, $nb_matching);
        return $results;
    }
    
    private function getSemanticFieldCriteria($fields, $name) {
        return isset($fields[$name]) ? $fields[$name] : '';
    }
    
    protected function getTrackerSemanticStatusJoin($is_super_user, $quoted_ugroups) {
        $semantic_status_join = "
            LEFT JOIN (
                tracker_changeset_value                 AS CV3
                INNER JOIN tracker_semantic_status      AS SS  ON (
                    CV3.field_id         = SS.field_id
                )
                INNER JOIN tracker_changeset_value_list AS CVL ON (
                    CV3.id               = CVL.changeset_value_id 
                    AND SS.open_value_id = CVL.bindvalue_id
                )";
        if (!$is_super_user) {
            /*$semantic_status_join .= "
                INNER JOIN permissions AS CVPerm3 ON (
                    CVPerm3.object_id           =  CAST(SS.field_id AS CHAR)
                    AND CVPerm3.permission_type =  'PLUGIN_TRACKER_FIELD_READ'
                    AND CVPerm3.ugroup_id       IN ($quoted_ugroups)
                )";*/
        }
        $semantic_status_join .= "
            ) ON (c.id = CV3.changeset_id)
            
            LEFT JOIN tracker_semantic_status AS SS2
            ON (artifact.tracker_id = SS2.tracker_id AND CVL.bindvalue_id IS NULL)
        ";
        return $semantic_status_join;
    }
    
    protected function getTrackerSemanticTitleJoin($is_super_user, $quoted_ugroups) {
        $semantic_title_join = "
            LEFT JOIN (
                tracker_changeset_value                 AS CV
                INNER JOIN tracker_semantic_title       AS ST  ON (
                    CV.field_id = ST.field_id
                )
                INNER JOIN tracker_changeset_value_text AS CVT ON (
                    CV.id       = CVT.changeset_value_id
                )";
        if (!$is_super_user) {
            /*$semantic_title_join .= "
                INNER JOIN permissions AS CVPerm ON (
                    CVPerm.object_id = CAST(ST.field_id AS CHAR)
                    AND CVPerm.permission_type = 'PLUGIN_TRACKER_FIELD_READ'
                    AND CVPerm.ugroup_id IN ($quoted_ugroups)
                )";*/
        }
        $semantic_title_join .= "
            ) ON (c.id = CV.changeset_id)";
        return $semantic_title_join;
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
        $open_condition = "(SS.open_value_id IS NOT NULL OR
                            SS2.open_value_id IS NULL)";
        switch ($status) {
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN:
            return " AND $open_condition ";
            break;
        case Tracker_CrossSearch_SemanticStatusReportField::STATUS_CLOSED:
            return " AND NOT $open_condition ";
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
    protected function getArtifactLinkSearchSqlFragment(array $artifacts_fields) {
        $artifact_link_search = '';
        foreach ($artifacts_fields as $field_id => $artifact_ids) {
            $field_id     = $this->da->quoteSmart($field_id);
            $artifact_ids = $this->da->quoteSmartImplode(',', $artifact_ids);
            
            $tracker_artifact                     = 'ALS_A_'.$field_id;
            $tracker_changeset_value              = 'ALS_CV_'.$field_id;
            $tracker_changeset_value_artifactlink = 'ALS_CVAL_'.$field_id;
            
            $artifact_link_search .= "
            INNER JOIN tracker_artifact                     AS $tracker_artifact                     ON (
                $tracker_artifact.id IN ($artifact_ids)
            )
            INNER JOIN tracker_changeset_value              AS $tracker_changeset_value              ON (
                $tracker_artifact.last_changeset_id   = $tracker_changeset_value.changeset_id
                AND $tracker_changeset_value.field_id = $field_id
            )
            INNER JOIN tracker_changeset_value_artifactlink AS $tracker_changeset_value_artifactlink ON (
               	artifact.id                     = $tracker_changeset_value_artifactlink.artifact_id
               	AND $tracker_changeset_value.id = $tracker_changeset_value_artifactlink.changeset_value_id
            )";
        }
        return $artifact_link_search;
    }
    
    
    /**
     * Find artifact link fields used by given artifacts
     * 
     * @param String $artifact_ids_list
     * 
     * @return Array of artifact_id indexed by the field they belongs to 
     */
    protected function getArtifactLinkFields($artifact_ids_list, $is_super_user, $quoted_ugroups) {
        $artifacts_fields = array();
        $permissions      = '';
        /*if (!$is_super_user) {
            $permissions  = "
                INNER JOIN permissions		  AS P ON (
                	P.object_id          =  CAST(F.id AS CHAR)
                    AND P.permission_type=  'PLUGIN_TRACKER_FIELD_READ'
                    AND P.ugroup_id      IN ($quoted_ugroups)
                )";
        } */
        $sql = "SELECT F.id AS field_id, A.id AS artifact_id
                FROM tracker_field            AS F
                INNER JOIN tracker            AS T ON (
	               	F.tracker_id = T.id
                )
                INNER JOIN tracker_artifact   AS A ON (
    	           	T.id = A.tracker_id
                )
                $permissions
                WHERE A.id IN ($artifact_ids_list)
                	AND formElement_type = 'art_link'
	                AND use_it = 1
        ";
        
        if (($dar = $this->retrieve($sql))) {
            foreach ($dar as $row) {
                $artifacts_fields[$row['field_id']][] = $row['artifact_id'];
            }
        }
        return $artifacts_fields;
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
    protected function getArtifactLinkColumns(array $field_ids, $is_super_user, $quoted_ugroups) {
        $artifact_link_columns = '';
        /*if ($is_super_user) {
            $permissions_template = '';
        } else {
            $permissions_template = "
                INNER JOIN permissions                          AS AL_COL_PERM_{field_id} ON (
                    AL_COL_PERM_{field_id}.object_id          =  CAST({field_id} AS CHAR)
                    AND AL_COL_PERM_{field_id}.permission_type=  'PLUGIN_TRACKER_FIELD_READ'
                    AND AL_COL_PERM_{field_id}.ugroup_id      IN ($quoted_ugroups)
                )";
        }*/
        foreach ($field_ids as $field_id) {
            $tracker_artifact_title        = 'AL_COL_'.$field_id;
            $al_tracker_changeset_value    = 'AL_COL_CV_'.$field_id;
            $al_tracker_changeset_value_al = 'AL_COL_CVAL_'.$field_id;
            $permissions = '';//str_replace('{field_id}', $field_id, $permissions_template);
                    
            $artifact_link_columns .= "
            LEFT JOIN (
            	tracker_artifact                                AS $tracker_artifact_title
                INNER JOIN tracker_changeset_value              AS $al_tracker_changeset_value   ON (
                    $tracker_artifact_title.last_changeset_id =  $al_tracker_changeset_value.changeset_id 
                    AND $al_tracker_changeset_value.field_id  IN ($field_id)
                )
                INNER JOIN tracker_changeset_value_artifactlink AS $al_tracker_changeset_value_al ON (
                    $al_tracker_changeset_value.id            = $al_tracker_changeset_value_al.changeset_value_id
                )
                $permissions
            ) ON ($al_tracker_changeset_value_al.artifact_id = artifact.id)";
        }
        return $artifact_link_columns;
    }

}
?>
