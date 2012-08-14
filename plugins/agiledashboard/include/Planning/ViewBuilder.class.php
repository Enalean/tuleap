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
require_once 'SearchContentView.class.php';
require_once 'BacklogItemFilterVisitor.class.php';
require_once 'GroupByParentsVisitor.class.php';

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
    
        $backlog_hierarchy = $this->hierarchy_factory->getHierarchy(array($backlog_tracker_id));

        $report      = $this->getReport($user);
        $criteria    = $this->getCriteria($user, $project, $report, $cross_search_query);
        $tracker_ids = $backlog_hierarchy->flatten();
        $artifacts   = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $already_planned_artifact_ids);

        // The following lines allows to tailor/rebuild the result before display
        // As of today (aug-12), we decided to display everything and to wait for
        // user feedback to see if we need to enable one of them.
        //$visitor     = new Planning_BacklogItemFilterVisitor($backlog_tracker_id, $this->hierarchy_factory, $already_planned_artifact_ids);
        //$artifacts   = $artifacts->accept($visitor);
        //$visitor     = new Planning_GroupByParentsVisitor($user);
        //$artifacts->accept($visitor);

        $backlog_actions_presenter = new Planning_BacklogActionsPresenter($planning->getBacklogTracker(), $planning_redirect_parameter);

        return new Planning_SearchContentView($report, 
                                              $criteria, 
                                              $artifacts, 
                                              Tracker_ArtifactFactory::instance(), 
                                              $this->form_element_factory,
                                              $user,
                                              $backlog_actions_presenter,
                                              $planning,
                                              $planning_redirect_parameter);        
    }

    public function setHierarchyFactory(Tracker_HierarchyFactory $hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
    }
}

?>
