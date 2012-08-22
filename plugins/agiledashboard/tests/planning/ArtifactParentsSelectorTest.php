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

class ArtifactParentsSelectorTest extends TuleapTestCase {

    protected $faq_id     = 13;
    protected $corp_id    = 42;
    protected $product_id = 56;
    protected $release_id = 7777;
    protected $sprint_id  = 9001;
    protected $theme_id   = 750;
    protected $epic_id    = 2;

    public function setUp() {
        parent::setUp();
        // corporation     -----> theme
        // `- product      -----> epic
        //    `- release   -----> epic
        //       `- sprint -----> story
        // faq
        $this->corp_tracker    = aTracker()->build();
        $this->product_tracker = aTracker()->build();
        $this->release_tracker = aTracker()->build();
        $this->sprint_tracker  = aTracker()->build();
        $this->epic_tracker    = aTracker()->build();
        $this->theme_tracker   = aTracker()->build();
        $this->faq_tracker     = aTracker()->build();
        $this->story_tracker   = aTracker()->build();

        $corp_planning    = stub('Planning')->getBacklogTracker()->returns($this->theme_tracker);
        $product_planning = stub('Planning')->getBacklogTracker()->returns($this->epic_tracker);
        $release_planning = stub('Planning')->getBacklogTracker()->returns($this->epic_tracker);
        $sprint_planning  = stub('Planning')->getBacklogTracker()->returns($this->story_tracker);

        $planning_factory = mock('PlanningFactory');
        stub($planning_factory)->getPlanningByPlanningTracker($this->corp_tracker)->returns($corp_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->product_tracker)->returns($product_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->release_tracker)->returns($release_planning);
        stub($planning_factory)->getPlanningByPlanningTracker($this->sprint_tracker)->returns($sprint_planning);

        $this->user   = aUser()->build();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');

        $this->faq     = $this->getArtifact($this->faq_id,     $this->faq_tracker,     array());
        $this->corp    = $this->getArtifact($this->corp_id,    $this->corp_tracker,    array());
        $this->product = $this->getArtifact($this->product_id, $this->product_tracker, array($this->corp));
        $this->release = $this->getArtifact($this->release_id, $this->release_tracker, array($this->product, $this->corp));
        $this->sprint  = $this->getArtifact($this->sprint_id,  $this->sprint_tracker,  array($this->release, $this->product, $this->corp));
        $this->theme   = $this->getArtifact($this->theme_id,   $this->theme_tracker,   array());
        $this->epic    = $this->getArtifact($this->epic_id,    $this->epic_tracker,    array($this->theme));

        $this->selector = new Planning_ArtifactParentsSelector($artifact_factory, $planning_factory);
    }

    private function getArtifact($id, Tracker $tracker, array $ancestors) {
        $artifact = aMockArtifact()->withId($id)->withTracker($tracker)->build();
        stub($artifact)->getAllAncestors($this->user)->returns($ancestors);
        stub($this->artifact_factory)->getArtifactById($id)->returns($artifact);
        return $artifact;
    }

    public function itProvidesItselfWhenReleaseIsLinkedToAProduct() {
        $expected = array($this->product);
        $this->assertEqual($expected, $this->selector->getPossibleParents($this->product_tracker, $this->product, $this->user));
    }
}
?>
