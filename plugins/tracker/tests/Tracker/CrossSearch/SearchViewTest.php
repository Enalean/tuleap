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

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

require_once dirname(__FILE__) . '/../../Test_Tracker_Builder.php';
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/SearchView.class.php';

Mock::generate('Service');
Mock::generate('Project');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_SharedFormElementFactory');
Mock::generate('Tracker_Artifact_Changeset');

class Tracker_CrossSearch_SearchViewTest extends TuleapTestCase {
    
    function testRenderShouldDisplayServiceHeaderAndFooter() {
        $service = new MockService();
        $service->expectOnce('displayHeader');
        $service->expectOnce('displayFooter');
        $criteria = $this->GivenCriteria();
        
        $view = $this->GivenASearchView($service, $criteria, array(), new TreeNode());
        
        $output = $this->renderAndGetContent($view);
    }
    
    function itRendersTheTrackerHomeNav() {
        $service  = new MockService();
        $criteria = $this->GivenCriteria();
        $view     = $this->GivenASearchView($service, $criteria, array(), new TreeNode());
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/id="tracker-home-nav"/', $output);
    }
    
    function testRenderShouldNotDisplayTableWhenNoMatchingArtifacts() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array();
        $view = $this->GivenASearchView($service, $criteria, $artifacts, new TreeNode());
        
        $GLOBALS['Language']->setReturnValue('getText', 'No matching artifact', array('plugin_tracker_crosssearch', 'no_matching_artifact'));
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/No matching artifact/', $output);
    }
    
    function testRenderShouldDisplayArtifacts() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array(
            array(
                'id'                => '6',
                'last_changeset_id' => '12345',
                'title'             => 'As a user I want to search on shared fields',
                'artifactlinks'     => '',
            ),
            array(
                'id'                => '8',
                'last_changeset_id' => '56789',
                'title'             => 'Add the form',
                'artifactlinks'     => '',
            )
        );
        
        
        $root = new TreeNode();
        $root->addChild(new TreeNode($artifacts[0]));
        $root->addChild(new TreeNode($artifacts[1]));
        
        $view = $this->GivenASearchView($service, $criteria, $artifacts, $root);
        
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
                'artifactlinks'     => '',
            )
        );
        
        $root = new TreeNode();
        $root->addChild(new TreeNode($artifacts[0]));
        
        $view = $this->GivenASearchView($service, $criteria, $artifacts, $root);
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('/As a user I want to search on shared fields/', $output);
        $this->assertPattern('/shared field value/', $output);
    }
    
    function testRenderShouldPadTheArtifactsAccordingToTheirLevel() {
        $service   = new MockService();
        $criteria  = $this->GivenCriteria();
        $artifacts = array(
            array(
                'id'                => '6',
                'last_changeset_id' => '12345',
                'title'             => 'As a user I want to search on shared fields',
                'artifactlinks'     => '8',
            ),
            array(
                'id'                => '8',
                'last_changeset_id' => '56789',
                'title'             => 'Add the form',
                'artifactlinks'     => '',
            )
        );
        
        $root  = new TreeNode();
        $node0 = new TreeNode($artifacts[0]);
        $node0->setId($artifacts[0]['id']);
        $node1 = new TreeNode($artifacts[1]);
        $node1->setId($artifacts[1]['id']);
        
        $root->addChild($node0);
        $node0->addChild($node1);
        
        $view = $this->GivenASearchView($service, $criteria, $artifacts, $root);
        
        $output = $this->renderAndGetContent($view);
        
        $this->assertPattern('%div class="tree-last tree-collapsable" id="tree-node-6"%', $output);
        $this->assertPattern('%div class="tree-blank" >[^<]*</div><div class="tree-last"%', $output);
    }
    
    private function GivenASearchView($service, $criteria, $artifacts, $root) {
        $report             = new MockTracker_Report();
        $artifact_factory   = $this->GivenAnArtifactFactory($artifacts);
        $shared_factory     = $this->GivenASharedFactory($criteria);
        $project            = new MockProject();
        $project->setReturnValue('getID', 110);
        $project->setReturnValue('getPublicName', 'gpig');
        $tracker1           = aTracker()->withId(101)->withName('Stories')->withProject($project)->build();
        $trackers           = array($tracker1);
        
        $this->setContentView($report, $criteria, $root, $artifact_factory, $shared_factory);
        $view               = new Tracker_CrossSearch_SearchView($project, $service, $criteria, $trackers);
        return $view;
    }
    
    private function GivenASharedFactory($criteria) {
        $shared_factory = new MockTracker_FormElementFactory();
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
        $artifact->setReturnValue('getTracker', new MockTracker());
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
        $view->render($this->content_view);
        $output = ob_get_clean();
        return $output;
    }

    private function setContentView($report, $criteria, $root, $artifact_factory, $shared_factory) {
        $this->content_view = new Tracker_CrossSearch_SearchContentView($report, $criteria, $root, $artifact_factory, $shared_factory);
    }
}
?>
