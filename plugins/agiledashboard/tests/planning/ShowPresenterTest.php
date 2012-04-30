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

require_once 'common/user/User.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/Tracker/CrossSearch/SearchContentView.class.php';
require_once dirname(__FILE__).'/../../include/Planning/Planning.class.php';
require_once dirname(__FILE__).'/../../include/Planning/ShowPresenter.class.php';

class Planning_ShowPresenterTest extends TuleapTestCase {
    public function itProvidesThePlanningTrackerArtifactCreationUrl() {
        $planning_tracker_id = 191;
        $planning_tracker    = mock('Tracker');
        $planning            = mock('Planning');
        $content_view        = mock('Tracker_CrossSearch_SearchContentView');
        $artifacts_to_select = array();
        $artifact            = null;
        $user                = mock('User');
        
        $origin_url = '/plugins/agiledashboard/?group_id=104&action=show&planning_id=5&aid=17';
        
        stub($planning)->getPlanningTrackerId()->returns($planning_tracker_id);
        stub($planning)->getPlanningTracker()->returns($planning_tracker);
        stub($planning_tracker)->getId()->returns($planning_tracker_id);
        
        $presenter = new Planning_ShowPresenter($planning,
                                                $content_view,
                                                $artifacts_to_select,
                                                $artifact,
                                                $user,
                                                $origin_url);
        
        $url = $presenter->getPlanningTrackerArtifactCreationUrl();
        
        $expected_return_to = urlencode($origin_url);
        $this->assertEqual($url, "/plugins/tracker/?tracker=191&func=new-artifact&return_to=$expected_return_to");
    }
    
}
?>
