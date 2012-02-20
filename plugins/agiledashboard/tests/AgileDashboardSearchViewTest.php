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

require_once dirname(__FILE__) .'/../include/AgileDashboard/SearchView.class.php';

Mock::generate('Service');
Mock::generate('Project');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class AgileDashboardViewTest extends TuleapTestCase {
    
    function testRenderShouldDisplayServiceHeaderAndFooter() {
        $service = new MockService();
        $service->expectOnce('displayHeader');
        $service->expectOnce('displayFooter');
        $criteria = array();
        
        $view = $this->GivenASearchView($service, $criteria, array());
        
        ob_start();
        $view->render();
        ob_end_clean();
    }
    
    function testRenderShouldDisplayArtifacts() {
        $service = new MockService();
        $criteria = array();
        $artifacts = array(
            array(
                'id' => '6',
                'title' => 'As a user I want to search on shared fields',
            ),
            array(
                'id' => '8',
                'title' => 'Add the form',
            )
        );
        
        $view = $this->GivenASearchView($service, $criteria, $artifacts);
        
        ob_start();
        $view->render();
        $output = ob_get_clean();
        
        $this->assertPattern('/As a user I want to search on shared fields/', $output);
        $this->assertPattern('/Add the form/', $output);
    }
    
    private function GivenASearchView($service, $criteria, $artifacts) {
        $report = new MockTracker_Report();
        $factory = $this->GivenAnArtifactFactory($artifacts);
        $view = new AgileDashboard_SearchView($service, $GLOBALS['Language'], $report, $criteria, $artifacts, $factory);
        return $view;
    }
    
    private function GivenAnArtifactFactory($artifacts) {
        $factory = new MockTracker_ArtifactFactory();
        foreach ($artifacts as $row) {
            $artifact = $this->GivenAnArtifact($row['id']);
            $factory->setReturnValue('getArtifactById', $artifact, array($row['id']));
        }
        return $factory;
    }
    
    private function GivenAnArtifact() {
        $artifact = new MockTracker_Artifact();
        $artifact->expectOnce('fetchDirectLinkToArtifact');
        return $artifact;
    }
}
?>
