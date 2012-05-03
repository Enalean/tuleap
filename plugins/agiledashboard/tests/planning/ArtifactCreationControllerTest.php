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

require_once dirname(__FILE__).'/../../include/Planning/ArtifactCreationController.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once dirname(__FILE__).'/../builders/planning.php';

class Planning_ArtifactCreationControllerTest extends TuleapTestCase {
    public function itRedirectsToArtifactCreationForm() {
        $planning_id         = 99876387;
        $aid                 = -1;
        $planning_tracker_id = 33;
        
        $request = new Codendi_Request(array(
            'planning_id' => $planning_id,
            'aid'         => $aid,
        ));
        $_SERVER['REQUEST_URI'] = "/plugins/agiledashboard/?group_id=104&action=show&planning_id=$planning_id&aid=$aid";
        
        $planning         = aPlanning()->withPlanningTrackerId($planning_tracker_id)->build();
        $planning_factory = mock('PlanningFactory');
        
        stub($planning_factory)->getPlanning($planning_id)->returns($planning);
        
        $return_url       = urlencode($request->getUri());
        $controller       = new Planning_ArtifactCreationController($planning_factory, $request);
        $new_artifact_url = TRACKER_BASE_URL."/\?tracker=$planning_tracker_id&func=new-artifact";
        $GLOBALS['Response']->expectOnce('redirect', array(new PatternExpectation("%$new_artifact_url%")));
        
        $controller->createArtifact();
    }
    public function itReturnsToTheCurrentUrlWithoutAidReference() {
        $planning_id         = 99876387;
        $aid                 = -1;
        $planning_tracker_id = 66;
        
        $request = new Codendi_Request(array(
            'planning_id' => $planning_id,
            'aid'         => $aid,
        ));
        $_SERVER['REQUEST_URI'] = "/plugins/agiledashboard/?group_id=104&action=show&planning_id=$planning_id&aid=$aid";
        
        $planning         = aPlanning()->withPlanningTrackerId($planning_tracker_id)->build();
        $planning_factory = mock('PlanningFactory');
        
        stub($planning_factory)->getPlanning($planning_id)->returns($planning);
        
        $return_url       = urlencode($request->getUri());
        $controller       = new Planning_ArtifactCreationController($planning_factory, $request);

        $aid_surrounded_by_ampersand_and_equals = urlencode('&').'aid'.urlencode('=');
        $GLOBALS['Response']->expectOnce('redirect', array(new NoPatternExpectation("/$aid_surrounded_by_ampersand_and_equals/")));        
        $controller->createArtifact();
    }

}
?>
