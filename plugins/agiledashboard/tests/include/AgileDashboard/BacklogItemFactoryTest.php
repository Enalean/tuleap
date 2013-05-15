<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../../common.php';

class BacklogItemFactoryTest extends TuleapTestCase {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AgileDashboard_BacklogItemFactory */
    private $factory;

    public function setUp() {
        parent::setUp();

        $this->dao = mock('AgileDashboard_BacklogItemDao');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');

        $this->factory = partial_mock('AgileDashboard_BacklogItemFactory', array('getBacklogArtifacts'), array($this->dao, $this->artifact_factory));
    }

    public function itCreatesContentWithOneElementInTodo() {
        $story1 = anArtifact()->withId(12)->build();
        stub($this->factory)->getBacklogArtifacts()->returns(array($story1));

        stub($this->artifact_factory)->getParents()->returns(array());

        stub($this->dao)->getArtifactsStatusAndTitle(array(12))->returnsDar(array('id' => 12, 'title' => 'Story blabla', 'status' => AgileDashboard_BacklogItemDao::STATUS_OPEN));

        $milestone = mock('Planning_ArtifactMilestone');
        $content = $this->factory->getMilestoneContentPresenter($milestone);

        $row = $content->todo_collection()->current();
        $this->assertEqual($row->id(), 12);
    }

    public function itCreatesContentWithOneElementInDone() {
        $story1 = anArtifact()->withId(12)->build();
        stub($this->factory)->getBacklogArtifacts()->returns(array($story1));

        stub($this->artifact_factory)->getParents()->returns(array());

        stub($this->dao)->getArtifactsStatusAndTitle(array(12))->returnsDar(array('id' => 12, 'title' => 'Story blabla', 'status' => AgileDashboard_BacklogItemDao::STATUS_CLOSED));

        $milestone = mock('Planning_ArtifactMilestone');
        $content = $this->factory->getMilestoneContentPresenter($milestone);

        $row = $content->done_collection()->current();
        $this->assertEqual($row->id(), 12);
    }
}

?>
