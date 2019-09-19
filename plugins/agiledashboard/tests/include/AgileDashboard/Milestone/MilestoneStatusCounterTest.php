<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once dirname(__FILE__).'/../../../bootstrap.php';

abstract class AgileDashboard_Milestone_MilestoneStatusCounterBaseTest extends TuleapTestCase
{

    protected $backlog_dao;
    protected $counter;
    protected $artifact_dao;
    protected $artifact_factory;
    protected $user;

    public function setUp()
    {
        parent::setUp();
        $this->backlog_dao      = mock('AgileDashboard_BacklogItemDao');
        $this->artifact_dao     = mock('Tracker_ArtifactDao');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->user             = aUser()->build();
        $this->counter          = new AgileDashboard_Milestone_MilestoneStatusCounter(
            $this->backlog_dao,
            $this->artifact_dao,
            $this->artifact_factory
        );
    }
}

class AgileDashboard_Milestone_MilestoneStatusCounterTest extends AgileDashboard_Milestone_MilestoneStatusCounterBaseTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->artifact_factory)->getArtifactById()->returns(aMockArtifact()->allUsersCanView()->build());
    }

    public function itDoesntFetchAnythingWhenNoMilestoneId()
    {
        expect($this->backlog_dao)->getBacklogArtifacts()->never();
        $result = $this->counter->getStatus($this->user, null);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 0,
           Tracker_Artifact::STATUS_CLOSED => 0,
        ));
    }

    public function itReturnsZeroOpenClosedWhenNoArtifacts()
    {
        stub($this->backlog_dao)->getBacklogArtifacts(12)->returnsEmptyDar();
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 0,
           Tracker_Artifact::STATUS_CLOSED => 0,
        ));
    }

    public function itDoesntTryToFetchChildrenWhenNoBacklog()
    {
        stub($this->backlog_dao)->getBacklogArtifacts()->returnsEmptyDar();
        expect($this->artifact_dao)->getChildrenForArtifacts()->never();
        $this->counter->getStatus($this->user, 12);
    }

    public function itFetchesTheStatusOfReturnedArtifacts()
    {
        stub($this->backlog_dao)->getBacklogArtifacts(12)->returnsDar(array('id' => 35), array('id' => 36));
        stub($this->artifact_dao)->getArtifactsStatusByIds(array(35, 36))->returnsDar(
            array('id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN),
            array('id' => 35, 'status' => Tracker_Artifact::STATUS_CLOSED)
        );
        stub($this->artifact_dao)->getChildrenForArtifacts()->returnsEmptyDar();
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 1,
           Tracker_Artifact::STATUS_CLOSED => 1,
        ));
    }

    public function itFetchesTheStatusOfReturnedArtifactsAtSublevel()
    {
        // Level 0
        stub($this->backlog_dao)->getBacklogArtifacts(12)->returnsDar(array('id' => 35), array('id' => 36));
        stub($this->artifact_dao)->getArtifactsStatusByIds(array(35, 36))->returnsDar(
            array('id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN),
            array('id' => 35, 'status' => Tracker_Artifact::STATUS_CLOSED)
        );

        // Level -1
        stub($this->artifact_dao)->getChildrenForArtifacts(array(35, 36))->returnsDar(
            array('id' => 38),
            array('id' => 39),
            array('id' => 40)
        );
        stub($this->artifact_dao)->getArtifactsStatusByIds(array(38, 39, 40))->returnsDar(
            array('id' => 38, 'status' => Tracker_Artifact::STATUS_OPEN),
            array('id' => 39, 'status' => Tracker_Artifact::STATUS_CLOSED),
            array('id' => 40, 'status' => Tracker_Artifact::STATUS_CLOSED)
        );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 2,
           Tracker_Artifact::STATUS_CLOSED => 3,
        ));
    }
}

class AgileDashboard_Milestone_MilestoneStatusCounter_PermissionsTest extends AgileDashboard_Milestone_MilestoneStatusCounterBaseTest
{

    public function itDoesntCountBacklogElementNotReadable()
    {
        stub($this->artifact_factory)->getArtifactById(35)->returns(aMockArtifact()->build()); // userCanView will return false by default
        stub($this->artifact_factory)->getArtifactById(36)->returns(aMockArtifact()->allUsersCanView()->build());

        stub($this->backlog_dao)->getBacklogArtifacts(12)->returnsDar(array('id' => 35), array('id' => 36));
        stub($this->artifact_dao)->getArtifactsStatusByIds(array(36))->returnsDar(
            array('id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN)
        );
        stub($this->artifact_dao)->getChildrenForArtifacts()->returnsEmptyDar();
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 1,
           Tracker_Artifact::STATUS_CLOSED => 0,
        ));
    }

    public function itDoesntCountSubElementsNotReadable()
    {
        stub($this->artifact_factory)->getArtifactById(36)->returns(aMockArtifact()->allUsersCanView()->build());
        stub($this->artifact_factory)->getArtifactById(37)->returns(aMockArtifact()->build()); // userCanView will return false by default
        stub($this->artifact_factory)->getArtifactById(38)->returns(aMockArtifact()->allUsersCanView()->build());

        stub($this->backlog_dao)->getBacklogArtifacts(12)->returnsDar(array('id' => 36));
        stub($this->artifact_dao)->getArtifactsStatusByIds(array(36))->returnsDar(
            array('id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN)
        );
        stub($this->artifact_dao)->getChildrenForArtifacts()->returnsDar(
            array('id' => 37),
            array('id' => 38)
        );

        stub($this->artifact_dao)->getArtifactsStatusByIds(array(38))->returnsDar(
            array('id' => 38, 'status' => Tracker_Artifact::STATUS_OPEN)
        );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEqual($result, array(
           Tracker_Artifact::STATUS_OPEN   => 2,
           Tracker_Artifact::STATUS_CLOSED => 0,
        ));
    }
}
