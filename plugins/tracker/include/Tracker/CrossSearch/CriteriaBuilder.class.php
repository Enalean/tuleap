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

require_once 'ArtifactReportField.class.php';


/**
 * Builds the cross-tracker search criteria, based on the request content.
 */
class Tracker_CrossSearch_CriteriaBuilder {

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    
    /**
     * @var Tracker_CrossSearch_SemanticValueFactory
     */
    private $semantic_value_factory;

    /**
     * @var Array of Tracker
     */
    private $planning_trackers;
    
    public function __construct(Tracker_FormElementFactory               $form_element_factory, 
                                Tracker_CrossSearch_SemanticValueFactory $semantic_value_factory,
                                array                                    $planning_trackers) {
        $this->form_element_factory   = $form_element_factory;
        $this->semantic_value_factory = $semantic_value_factory;
        $this->planning_trackers      = $planning_trackers;
    }
    
    /**
     * @return array of \Tracker_Report_Criteria 
     */
    public function getCriteria(User $user, Project $project, Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        $shared_fields   = $this->getSharedFieldsCriteria($user, $project, $report, $cross_search_query);
        $semantic_fields = $this->getSemanticFieldsCriteria($report, $cross_search_query);
        $artifact_fields = $this->getArtifactLinkCriteria($user, $report, $cross_search_query);
        return array_merge($semantic_fields, $shared_fields, $artifact_fields);
    }

    /**
     * @return array of \Tracker_Report_Criteria 
     */
    public function getSharedFieldsCriteria(User $user, Project $project, Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        $fields   = $this->form_element_factory->getSharedFieldsReadableBy($user, $project);
        $criteria = array();
        
        foreach ($fields as $field) {
                $field->setCriteriaValue($this->getSelectedValues($field, $cross_search_query->getSharedFields()));
                $id          = null;
                $rank        = 0;
                $is_advanced = true;
                $criteria[]  = new Tracker_Report_Criteria($id, $report, $field, $rank, $is_advanced);
        }
        
        return $criteria;
    }
    
    /**
     * @return array of \Tracker_Report_Criteria 
     */
    public function getSemanticFieldsCriteria(Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        $title_field  = new Tracker_CrossSearch_SemanticTitleReportField($cross_search_query->getTitle(), $this->semantic_value_factory);
        $status_field = new Tracker_CrossSearch_SemanticStatusReportField($cross_search_query->getStatus(), $this->semantic_value_factory);
        $id           = null;
        $rank         = 0;
        $is_advanced  = true;
        
        return array(
            new Tracker_Report_Criteria($id, $report, $title_field,  $rank, $is_advanced), 
            new Tracker_Report_Criteria($id, $report, $status_field, $rank, $is_advanced)
        );
    }

    public function getArtifactLinkCriteria(User $user, Tracker_Report $report, Tracker_CrossSearch_Query $cross_search_query) {
        $criteria = array();
        foreach ($this->planning_trackers as $tracker) {
            $tracker_id        = $tracker->getId();
            $tracker_artifacts = Tracker_ArtifactFactory::instance()->getArtifactsByTrackerIdUserCanView($user, $tracker_id);
            $tracker_artifacts = $cross_search_query->setSelectedArtifacts($tracker_id, $tracker_artifacts);
            $field      = new Tracker_CrossSearch_ArtifactReportField($tracker, $tracker_artifacts);
            $criteria[] = new Tracker_Report_Criteria(null, $report, $field, null, true);
        }
        return $criteria;
    }
    
    private function getSelectedValues(Tracker_FormElement_Field $field, $request_criteria) {
        $current_value = array();
        
        if (isset($request_criteria[$field->getId()]['values'])) {
            $current_value = $request_criteria[$field->getId()]['values'];
        }
        
        return $current_value;
    }

}

?>
