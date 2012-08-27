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


require_once dirname(__FILE__).'/../../include/autoload.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class ArtifactParentsSelectorEventListenerTest extends TuleapTestCase {

    protected $faq_id      = 13;
    protected $corp_id     = 42;
    protected $product_id  = 56;
    protected $product2_id = 57;
    protected $release_id  = 7777;
    protected $release2_id = 7778;
    protected $sprint_id   = 9001;
    protected $theme_id    = 750;
    protected $theme2_id   = 751;
    protected $epic_id     = 2;
    protected $epic2_id    = 3;

    public function setUp() {
        parent::setUp();
        '┝ corporation    ──────≫ theme
         │ ┝ product      ──┬───≫  ┕ epic
         │ │  ┕ release   ──┘   ┌─≫   ┕ story
         │ │     ┕ sprint ──────┘
         │ ┕ product2
         │    ┕ release2
         ┕ faq
        ';
        $this->corp_tracker    = aTracker()->withName('corp_tracker')->build();
        $this->product_tracker = aTracker()->withName('product_tracker')->build();
        $this->release_tracker = aTracker()->withName('release_tracker')->build();
        $this->sprint_tracker  = aTracker()->withName('sprint_tracker')->build();
        $this->epic_tracker    = aTracker()->withName('epic_tracker')->build();
        $this->theme_tracker   = aTracker()->withName('theme_tracker')->build();
        $this->faq_tracker     = aTracker()->withName('faq_tracker')->build();
        $this->story_tracker   = aTracker()->withName('story_tracker')->build();

        $this->user    = aUser()->build();
        $this->request = mock('Codendi_Request');

        $this->artifact_factory  = mock('Tracker_ArtifactFactory');

        $this->faq      = $this->getArtifact($this->faq_id,     $this->faq_tracker);
        $this->corp     = $this->getArtifact($this->corp_id,    $this->corp_tracker);
        $this->product  = $this->getArtifact($this->product_id, $this->product_tracker);
        $this->product2 = $this->getArtifact($this->product2_id, $this->product_tracker);
        $this->release  = $this->getArtifact($this->release_id, $this->release_tracker);
        $this->release2 = $this->getArtifact($this->release2_id, $this->release_tracker);
        $this->sprint   = $this->getArtifact($this->sprint_id,  $this->sprint_tracker);
        $this->theme    = $this->getArtifact($this->theme_id,   $this->theme_tracker);
        $this->theme2   = $this->getArtifact($this->theme2_id,   $this->theme_tracker);
        $this->epic     = $this->getArtifact($this->epic_id,    $this->epic_tracker);
        $this->epic2    = $this->getArtifact($this->epic2_id,    $this->epic_tracker);

        stub($GLOBALS['Language'])->getText('plugin_agiledashboard', 'available', 'epic_tracker')->returns('Available epic_tracker');

        $this->selector = mock('Planning_ArtifactParentsSelector');
        stub($this->selector)->getPossibleParents($this->epic_tracker, $this->sprint, $this->user)->returns(array($this->epic, $this->epic2));
        stub($this->selector)->getPossibleParents($this->epic_tracker, $this->epic2, $this->user)->returns(array($this->epic2));


        $this->event_listener = new Planning_ArtifactParentsSelectorEventListener($this->artifact_factory, $this->selector, $this->request);
    }

    private function getArtifact($id, Tracker $tracker) {
        $artifact  = aMockArtifact()->withId($id)->withTracker($tracker)->build();
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        return $artifact;
    }

    public function itRetrievesThePossibleParentsForANewArtifactLink() {
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

    public function itRetrievesThePossibleParentsForChildMilestone() {
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

    public function itRetrievesNothingIfThereIsNoChildMilestoneNorNewArtifactLink() {
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

    public function itAsksForNoSelectorIfWeLinkToAParent() {
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
?>
