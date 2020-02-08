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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

class UserRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalResponseMock, GlobalLanguageMock;

    /**
     * @var UserRemover
     */
    private $remover;
    /**
     * @var \EventManager|\Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager     = \Mockery::spy(\ProjectManager::class);
        $this->event_manager       = \Mockery::spy(\EventManager::class);
        $this->tv3_tracker_factory = \Mockery::spy(\ArtifactTypeFactory::class);
        $this->dao                 = \Mockery::spy(\Tuleap\Project\UserRemoverDao::class);
        $this->user_manager        = \Mockery::spy(\UserManager::class);
        $this->project_history_dao = \Mockery::spy(\ProjectHistoryDao::class);
        $this->ugroup_manager      = \Mockery::spy(\UGroupManager::class);

        $this->remover = new UserRemover(
            $this->project_manager,
            $this->event_manager,
            $this->tv3_tracker_factory,
            $this->dao,
            $this->user_manager,
            $this->project_history_dao,
            $this->ugroup_manager
        );

        $this->project    = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->user       = new PFUser([
            'language_id' => 'en',
            'user_id' => 102
        ]);
        $this->tracker_v3 = \Mockery::spy(\ArtifactType::class);
    }

    public function testItRemovesUserFromProjectMembersAndUgroups()
    {
        $project_id = 101;
        $user_id    = 102;

        $this->dao->shouldReceive('removeUserFromProject')->once()->andReturns(true);
        $this->dao->shouldReceive('removeUserFromProjectUgroups')->once()->andReturns(true);
        $this->tracker_v3->shouldReceive('deleteUser')->once()->with(102)->andReturns(true);
        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($this->project);
        $this->user_manager->shouldReceive('getUserById')->with(102)->andReturns($this->user);
        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($this->project)->andReturns(array());
        $this->tv3_tracker_factory->shouldReceive('getArtifactTypesFromId')->with(101)->andReturns(array($this->tracker_v3));

        $this->project_history_dao->shouldReceive('groupAddHistory')->once();
        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->remover->removeUserFromProject($project_id, $user_id);
    }

    public function testItDoesNothingIfTheUserIsNotRemovedFromProjectMembers()
    {
        $project_id = 101;
        $user_id    = 102;

        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($this->project);

        $this->dao->shouldReceive('removeUserFromProject')->once();
        $this->dao->shouldReceive('removeUserFromProjectUgroups')->never();
        $this->project_history_dao->shouldReceive('groupAddHistory')->never();
        $this->tracker_v3->shouldReceive('deleteUser')->never();
        $this->event_manager->shouldReceive('processEvent')->never();

        $this->remover->removeUserFromProject($project_id, $user_id);
    }
}
