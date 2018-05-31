<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
require_once __DIR__ . '/../../../../tests/simpletest/common/include/builders/aTreeNode.php';

abstract class Planning_MilestoneTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->project   = stub('Project')->getID()->returns(123);
        $this->planning  = aPlanning()->withId(9999)->build();
    }

    public function itHasAPlanning() {
        $this->assertEqual($this->planning, $this->milestone->getPlanning());
        $this->assertEqual($this->planning->getId(), $this->milestone->getPlanningId());
    }

    public function itHasAProject() {
        $this->assertEqual($this->project, $this->milestone->getProject());
        $this->assertEqual($this->project->getID(), $this->milestone->getGroupId());
    }

}

class Planning_NoMilestoneTest extends Planning_MilestoneTest {
    public function setUp() {
        parent::setUp();
        $this->milestone = new Planning_NoMilestone($this->project, $this->planning);
    }
}

class Planning_ArtifactMilestoneTest extends Planning_MilestoneTest {

    /**
     * @var \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;
    protected $project;
    protected $planning;
    private $artifact;

    /**
     * @var Planning_Milestone
     */
    protected $milestone;

    public function setUp()
    {
        parent::setUp();

        $this->project  = stub('Project')->getID()->returns(123);
        $this->planning = aPlanning()->build();
        $this->artifact = aMockArtifact()->withTitle('Foo')->build();

        $this->scrum_mono_milestone_checker = mock('\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');

        $this->milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $this->scrum_mono_milestone_checker
        );
    }

    public function itRepresentsAnArtifact() {
        $this->assertEqual($this->milestone->getArtifact(), $this->artifact);
    }

    public function itDelegatesArtifactIdRetrieval() {
        $this->assertEqual($this->milestone->getArtifactId(), $this->artifact->getId());
    }

    public function itDelegatesArtifactTitleRetrieval()
    {
        $artifact = aMockArtifact()->withTitle('a simple little artifact')->build();
        $milestone = new Planning_ArtifactMilestone(
            $this->project, mock('Planning'), $artifact, $this->scrum_mono_milestone_checker
        );
        $this->assertEqual($milestone->getArtifactTitle(), $artifact->getTitle());
    }

    public function itMayHavePlannedArtifacts()
    {
        $this->assertEqual($this->milestone->getPlannedArtifacts(), null);

        $planned_artifacts = new ArtifactNode(aMockArtifact()->build());
        $this->milestone   = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $this->scrum_mono_milestone_checker,
            $planned_artifacts
        );

        $this->assertEqual($this->milestone->getPlannedArtifacts(), $planned_artifacts);
    }

    public function itMayBeNull() {
        $this->milestone = new Planning_NoMilestone($this->project, $this->planning);

        $this->assertNull($this->milestone->getArtifact());
        $this->assertNull($this->milestone->getArtifactId());
        $this->assertNull($this->milestone->getArtifactTitle());
        $this->assertTrue($this->milestone->userCanView(mock('PFUser')), "any user should be able to read an empty milstone");
    }

    public function itHasATitle()
    {
        $this->milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $this->scrum_mono_milestone_checker
        );
        $this->assertEqual($this->milestone->getArtifactTitle(), 'Foo');
    }
}

class Milestone_linkedArtifactTest extends TuleapTestCase {
    /**
     * @var \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        parent::setUp();
        $this->project                      = mock('Project');
        $this->scrum_mono_milestone_checker = mock('\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');
    }

    public function itGetsLinkedArtifactsOfTheRootLevelArtifact()
    {
        $artifact = aMockArtifact()->withId(1111)->withUniqueLinkedArtifacts(array(aMockArtifact()->build()))->build();

        $milestone     = new Planning_ArtifactMilestone(
            $this->project,
            mock('Planning'),
            $artifact,
            $this->scrum_mono_milestone_checker
        );
        $all_artifacts = $milestone->getLinkedArtifacts(mock('PFUser'));
        $this->assertEqual(count($all_artifacts), 1);
    }

    public function itGetsTheArtifactsChildNodes()
    {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $child1_artifact   = aMockArtifact()->withId(1111)->withTitle('child artifact 1')->build();
        $child2_artifact   = aMockArtifact()->withId(2222)->withTitle('child artifact 2')->build();
        $planned_artifacts = anArtifactNode()->withArtifact($root_artifact)
            ->withChildren(
                anArtifactNode()->withArtifact($child1_artifact),
                anArtifactNode()->withArtifact($child2_artifact)
            )
            ->build();


        $milestone     = new Planning_ArtifactMilestone(
            $this->project,
            mock('Planning'),
            $root_artifact,
            $this->scrum_mono_milestone_checker,
            $planned_artifacts
        );
        $all_artifacts = $milestone->getLinkedArtifacts(mock('PFUser'));
        $this->assertEqual(count($all_artifacts), 2);
    }

    public function itGetsTheArtifactsOfNestedChildNodes()
    {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $depth1_artifact   = aMockArtifact()->withId(1111)->withTitle('depth 1 artifact')->build();
        $depth2_artifact   = aMockArtifact()->withId(2222)->withTitle('depth 2 artifact')->build();
        $planned_artifacts = anArtifactNode()->withArtifact($root_artifact)
            ->withChild(
                anArtifactNode()->withArtifact($depth1_artifact)
                    ->withChild(anArtifactNode()->withArtifact($depth2_artifact))
            )
            ->build();


        $milestone     = new Planning_ArtifactMilestone(
            $this->project,
            mock('Planning'),
            $root_artifact,
            $this->scrum_mono_milestone_checker,
            $planned_artifacts
        );
        $all_artifacts = $milestone->getLinkedArtifacts(mock('PFUser'));
        $this->assertEqual(count($all_artifacts), 2);
    }

    public function itGetsTheLinkedArtifactsOfChildNodes()
    {
        $root_artifact     = aMockArtifact()->withId(9999)->withTitle('root artifact')->build();
        $linked_artifact_1 = aMockArtifact()->build();
        $linked_artifact_2 = aMockArtifact()->build();
        $artifact          = aMockArtifact()->withId(1111)
            ->withUniqueLinkedArtifacts(array($linked_artifact_1, $linked_artifact_2))
            ->build();
        $planned_artifacts = anArtifactNode()->withArtifact($root_artifact)
            ->withChild(
                anArtifactNode()->withArtifact($artifact)
            )
            ->build();


        $milestone     = new Planning_ArtifactMilestone(
            $this->project,
            mock('Planning'),
            $root_artifact,
            $this->scrum_mono_milestone_checker,
            $planned_artifacts
        );
        $all_artifacts = $milestone->getLinkedArtifacts(mock('PFUser'));
        $this->assertEqual(count($all_artifacts), 3);
    }
}
