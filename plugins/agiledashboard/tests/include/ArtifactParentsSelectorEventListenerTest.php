<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class ArtifactParentsSelectorEventListenerTest extends TuleapTestCase
{

    protected $sprint_id   = 9001;
    protected $epic_id     = 2;
    protected $epic2_id    = 3;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        '
                              epic
         ┕ sprint ───────≫   ┕ story
        ';

        $this->sprint_tracker  = aTracker()->withName('sprint_tracker')->build();
        $this->epic_tracker    = aTracker()->withName('epic_tracker')->build();
        $this->story_tracker   = aTracker()->withName('story_tracker')->build();

        $this->user    = aUser()->build();
        $this->request = \Mockery::spy(\Codendi_Request::class);

        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->sprint   = $this->getArtifact($this->sprint_id, $this->sprint_tracker);
        $this->epic     = $this->getArtifact($this->epic_id, $this->epic_tracker);
        $this->epic2    = $this->getArtifact($this->epic2_id, $this->epic_tracker);

        stub($GLOBALS['Language'])->getText('plugin_agiledashboard', 'available', 'epic_tracker')->returns('Available epic_tracker');

        $this->selector = \Mockery::spy(\Planning_ArtifactParentsSelector::class);
        stub($this->selector)->getPossibleParents($this->epic_tracker, $this->sprint, $this->user)->returns(array($this->epic, $this->epic2));
        stub($this->selector)->getPossibleParents($this->epic_tracker, $this->epic2, $this->user)->returns(array($this->epic2));

        $this->event_listener = new Planning_ArtifactParentsSelectorEventListener($this->artifact_factory, $this->selector, $this->request);
    }

    private function getArtifact($id, Tracker $tracker)
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        return $artifact;
    }

    public function itRetrievesThePossibleParentsForANewArtifactLink()
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        stub($this->request)->get('func')->returns('new-artifact-link');
        stub($this->request)->get('id')->returns($this->sprint_id);

        $this->event_listener->process($params);

        $this->assertEqual($label, 'Available epic_tracker');
        $this->assertEqual($possible_parents, array($this->epic, $this->epic2));
        $this->assertEqual($display_selector, true);
    }

    public function itRetrievesThePossibleParentsForChildMilestone()
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        stub($this->request)->get('func')->returns('new-artifact');
        stub($this->request)->get('child_milestone')->returns($this->sprint_id);

        $this->event_listener->process($params);

        $this->assertEqual($label, 'Available epic_tracker');
        $this->assertEqual($possible_parents, array($this->epic, $this->epic2));
        $this->assertEqual($display_selector, true);
    }

    public function itRetrievesNothingIfThereIsNoChildMilestoneNorNewArtifactLink()
    {
        $label            = 'untouched';
        $possible_parents = 'untouched';
        $display_selector = 'untouched';
        $params = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        stub($this->request)->get()->returns(false);

        $this->event_listener->process($params);

        $this->assertEqual($label, 'untouched');
        $this->assertEqual($possible_parents, 'untouched');
        $this->assertEqual($display_selector, 'untouched');
    }

    public function itAsksForNoSelectorIfWeLinkToAParent()
    {
        $label            = '';
        $possible_parents = '';
        $display_selector = true;
        $params = array(
            'user'             => $this->user,
            'parent_tracker'   => $this->epic_tracker,
            'label'            => &$label,
            'possible_parents' => &$possible_parents,
            'display_selector' => &$display_selector,
        );
        stub($this->request)->get('func')->returns('new-artifact');
        stub($this->request)->get('child_milestone')->returns($this->epic2_id);

        $this->event_listener->process($params);

        $this->assertEqual($possible_parents, array($this->epic2));
        $this->assertEqual($display_selector, false);
    }
}
