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
    
    public function build(User $user, 
                          Project $project,
                          Tracker_CrossSearch_Query $cross_search_query, 
                          array $excluded_artifact_ids, 
                          array $tracker_ids,
                          Planning $planning,
                          $planning_redirect_parameter) {
    
        $report    = $this->getReport($user);
        $criteria  = $this->getCriteria($user, $project, $report, $cross_search_query);
        $artifacts = $this->getHierarchicallySortedArtifacts($user, $project, $tracker_ids, $cross_search_query, $excluded_artifact_ids);
        
        return new Planning_SearchContentView($report, 
                                              $criteria, 
                                              $artifacts, 
                                              Tracker_ArtifactFactory::instance(), 
                                              $this->form_element_factory,
                                              $user,
                                              $planning,
                                              $planning_redirect_parameter);        
    }
    
    

}

?>
