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

require_once dirname(__FILE__).'/../../../tracker/include/Tracker/CrossSearch/ViewBuilder.class.php';
/**
 * This class builds the Planning_SearchContentView that is used to display the right column of the Planning
 */
class Planning_ViewBuilder extends Tracker_CrossSearch_ViewBuilder {
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    
    public function build(User $user, 
                          Project $project,
                          Tracker_CrossSearch_Query $cross_search_query, 
                          array $already_planned_artifact_ids,
                          $backlog_tracker_id,
                          Planning $planning,
                          $planning_redirect_parameter) {
    
        $report      = $this->getReport($user);
        $criteria    = $this->getCriteria($user, $project, $report, $cross_search_query);
        $tracker_ids = $this->hierarchy_factory->getHierarchy(array($backlog_tracker_id))->flatten();
        $artifacts   = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $already_planned_artifact_ids);
        $visitor     = new Planning_BacklogItemFilterVisitor($backlog_tracker_id, $this->hierarchy_factory, $already_planned_artifact_ids);
        $artifacts   = $artifacts->accept($visitor);
        
        return new Planning_SearchContentView($report, 
                                              $criteria, 
                                              $artifacts, 
                                              Tracker_ArtifactFactory::instance(), 
                                              $this->form_element_factory,
                                              $user,
                                              $planning,
                                              $planning_redirect_parameter);        
    }

    public function setHierarchyFactory(Tracker_HierarchyFactory $hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
    }

}

?>
