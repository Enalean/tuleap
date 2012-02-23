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

require_once dirname(__FILE__) . '/../../../tracker/tests/Test_Tracker_Builder.php';
require_once dirname(__FILE__) .'/../../include/AgileDashboard/SearchView.class.php';

Mock::generate('Service');
Mock::generate('Project');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_SharedFormElementFactory');
Mock::generate('Tracker_Artifact_Changeset');

class AgileDashboard_SearchViewTest extends TuleapTestCase {
    
    function testRenderShouldDisplayServiceHeaderAndFooter() {
        $service = new MockService();
        $service->expectOnce('displayHeader');
        $service->expectOnce('displayFooter');
        $criteria = $this->GivenCriteria();
        
        $view = $this->GivenASearchView($service, $criteria, array());
        
        $output = $this->renderAndGetContent($view);
    }
    
    function testRenderShouldNotDisplayTableWhenNoMatchingArtifacts() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array();
        $view = $this->GivenASearchView($service, $criteria, $artifacts);
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/No artifact/', $output);
    }
    
    function testRenderShouldDisplayArtifacts() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array(
            array(
                'id'                => '6',
                'last_changeset_id' => '12345',
                'title'             => 'As a user I want to search on shared fields',
            ),
            array(
                'id'                => '8',
                'last_changeset_id' => '56789',
                'title'             => 'Add the form',
            )
        );
        
        $view = $this->GivenASearchView($service, $criteria, $artifacts);
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/As a user I want to search on shared fields/', $output);
        $this->assertPattern('/Add the form/', $output);
    }
    
    function testRenderShouldDisplaySharedFieldValue() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array(
            array(
                'id'                => '6',
                'last_changeset_id' => '12345',
                'title'             => 'As a user I want to search on shared fields',
            )
        );
        $view = $this->GivenASearchView($service, $criteria, $artifacts);
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/As a user I want to search on shared fields/', $output);
        $this->assertPattern('/shared field value/', $output);
    }
    
    private function GivenASearchView($service, $criteria, $artifacts) {
        $report           = new MockTracker_Report();
        $artifact_factory = $this->GivenAnArtifactFactory($artifacts);
        $shared_factory   = $this->GivenASharedFactory($criteria);
        $project          = new MockProject();
        $project->setReturnValue('getID', 110);
        $project->setReturnValue('getPublicName', 'gpig');
        $tracker1         = aTracker()->withId(101)->withName('Stories')->withProject($project)->build();
        $trackers         = array($tracker1);
        $view             = new AgileDashboard_SearchView($service, $GLOBALS['Language'], $report, $criteria, $artifacts, $artifact_factory, $shared_factory, $trackers);
        return $view;
    }
    
    private function GivenASharedFactory($criteria) {
        $shared_factory = new MockTracker_SharedFormElementFactory();
        foreach ($criteria as $criterion) {
            $shared_factory->setReturnValue('getFieldFromTrackerAndSharedField', $criterion->field, array('*', $criterion->field));
        }
        return $shared_factory;
    }
    
    private function GivenAnArtifactFactory($artifacts) {
        $factory = new MockTracker_ArtifactFactory();
        foreach ($artifacts as $row) {
            $artifact = $this->GivenAnArtifact($row['id']);
            $factory->setReturnValue('getArtifactById', $artifact, array($row['id']));
        }
        return $factory;
    }
    
    private function GivenAnArtifact($id) {
        $artifact  = new MockTracker_Artifact();
        $artifact->expectOnce('fetchDirectLinkToArtifact');
        $artifact->setReturnValue('getId', $id);
        return $artifact;
    }
    
    private function GivenCriteria() {
        $criterion        = new stdClass();
        $criterion->field = new MockTracker_FormElement_Field_List();
        $criterion->field->setReturnValue('fetchChangesetValue', 'shared field value', array('6', '12345', null));
        $criteria = array($criterion);
        return $criteria;
    }
    
    private function renderAndGetContent($view) {
        ob_start();
        $view->render();
        $output = ob_get_clean();
        return $output;
    }
}
?>
