<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project;

require_once __DIR__ . '/../../../../src/www/include/exit.php';

use TuleapTestCase;

class UserRemoverTest extends TuleapTestCase
{
    /**
     * @var UserRemover
     */
    private $remover;
    /**
     * @var \EventManager|\Mockery\MockInterface
     */
    private $event_manager;

    public function setUp()
    {
        parent::setUp();

        $this->project_manager     = mock('ProjectManager');
        $this->event_manager       = \Mockery::spy(\EventManager::class);
        $this->tv3_tracker_factory = mock('ArtifactTypeFactory');
        $this->dao                 = mock('Tuleap\Project\UserRemoverDao');
        $this->user_manager        = mock('UserManager');
        $this->project_history_dao = mock('ProjectHistoryDao');
        $this->ugroup_manager      = mock('UGroupManager');

        $this->remover = new UserRemover(
            $this->project_manager,
            $this->event_manager,
            $this->tv3_tracker_factory,
            $this->dao,
            $this->user_manager,
            $this->project_history_dao,
            $this->ugroup_manager
        );

        $this->project    = aMockProject()->withId(101)->build();
        $this->user       = aUser()->withId(102)->build();
        $this->tracker_v3 = mock('ArtifactType');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function itRemovesUserFromProjectMembersAndUgroups()
    {
        $project_id = 101;
        $user_id    = 102;

        stub($this->dao)->removeUserFromProject()->returns(true);
        stub($this->dao)->removeUserFromProjectUgroups()->returns(true);
        stub($this->tracker_v3)->deleteUser(102)->returns(true);
        stub($this->project_manager)->getProject(101)->returns($this->project);
        stub($this->user_manager)->getUserById(102)->returns($this->user);
        stub($this->ugroup_manager)->getStaticUGroups($this->project)->returns(array());
        stub($this->tv3_tracker_factory)->getArtifactTypesFromId(101)->returns(array($this->tracker_v3));

        expect($this->dao)->removeUserFromProject()->once();
        expect($this->dao)->removeUserFromProjectUgroups()->once();
        expect($this->project_history_dao)->groupAddHistory()->once();
        expect($this->tracker_v3)->deleteUser()->once();
        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->remover->removeUserFromProject($project_id, $user_id);
    }

    public function itDoesNothingIfTheUserIsNotRemovedFromProjectMembers()
    {
        $project_id = 101;
        $user_id    = 102;

        stub($this->project_manager)->getProject(101)->returns($this->project);

        expect($this->dao)->removeUserFromProject()->once();
        expect($this->dao)->removeUserFromProjectUgroups()->never();
        expect($this->project_history_dao)->groupAddHistory()->never();
        expect($this->tracker_v3)->deleteUser()->never();
        expect($this->event_manager)->processEvent()->never();

        $this->remover->removeUserFromProject($project_id, $user_id);
    }
}
