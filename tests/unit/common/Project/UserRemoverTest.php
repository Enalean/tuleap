<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private UserRemover $remover;
    private MockObject&\EventManager $event_manager;
    private MockObject&\ProjectManager $project_manager;
    private MockObject&\ArtifactTypeFactory $tv3_tracker_factory;
    private MockObject&UserRemoverDao $dao;
    private MockObject&\UserManager $user_manager;
    private MockObject&\ProjectHistoryDao $project_history_dao;
    private MockObject&\UGroupManager $ugroup_manager;
    private \Project $project;
    private PFUser $user;
    private MockObject&\ArtifactType $tracker_v3;
    private MockObject&UserPermissionsDao $user_permissions_dao;


    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager      = $this->createMock(\ProjectManager::class);
        $this->event_manager        = $this->createMock(\EventManager::class);
        $this->tv3_tracker_factory  = $this->createMock(\ArtifactTypeFactory::class);
        $this->dao                  = $this->createMock(UserRemoverDao::class);
        $this->user_manager         = $this->createMock(\UserManager::class);
        $this->project_history_dao  = $this->createMock(\ProjectHistoryDao::class);
        $this->ugroup_manager       = $this->createMock(\UGroupManager::class);
        $this->user_permissions_dao = $this->createMock(UserPermissionsDao::class);

        $this->remover = new UserRemover(
            $this->project_manager,
            $this->event_manager,
            $this->tv3_tracker_factory,
            $this->dao,
            $this->user_manager,
            $this->project_history_dao,
            $this->ugroup_manager,
            $this->user_permissions_dao,
        );

        $this->project    = ProjectTestBuilder::aProject()->withId(101)->withUnixName("")->withAccess(\Project::ACCESS_PRIVATE)->build();
        $this->user       = new PFUser([
            'language_id' => 'en',
            'user_id' => 102,
        ]);
        $this->tracker_v3 = $this->createMock(\ArtifactType::class);
    }

    public function testItRemovesUserFromProjectMembersAndUgroups(): void
    {
        $project_id = 101;
        $user_id    = 102;

        $this->dao->expects(self::once())->method('removeNonAdminUserFromProject')->willReturn(true);
        $this->dao->expects(self::once())->method('removeUserFromProjectUgroups')->willReturn(true);
        $this->tracker_v3->expects(self::once())->method('deleteUser')->with(102)->willReturn(true);
        $this->project_manager->method('getProject')->with(101)->willReturn($this->project);
        $this->user_manager->method('getUserById')->with(102)->willReturn($this->user);
        $this->ugroup_manager->method('getStaticUGroups')->with($this->project)->willReturn([]);
        $this->tv3_tracker_factory->method('getArtifactTypesFromId')->with(101)->willReturn([$this->tracker_v3]);

        $this->project_history_dao->expects(self::once())->method('groupAddHistory');
        $this->event_manager->expects(self::exactly(2))->method('processEvent');

        $this->remover->removeUserFromProject($project_id, $user_id);
    }

    public function testItForcesRemovalOfRestrictedUserFromProjectAdminAndUgroups(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aRestrictedUser()->withId(102)->build();

        $this->user_manager->method('getUserAnonymous')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $this->user_permissions_dao->expects(self::once())->method('removeUserFromProjectAdmin');
        $this->dao->expects(self::once())->method('removeNonAdminUserFromProject')->willReturn(true);
        $this->dao->expects(self::once())->method('removeUserFromProjectUgroups')->willReturn(true);
        $this->tracker_v3->expects(self::once())->method('deleteUser')->with(102)->willReturn(true);
        $this->project_manager->method('getProject')->with(101)->willReturn($this->project);
        $this->user_manager->method('getUserById')->with(102)->willReturn($this->user);
        $this->ugroup_manager->method('getStaticUGroups')->with($this->project)->willReturn([]);
        $this->tv3_tracker_factory->method('getArtifactTypesFromId')->with(101)->willReturn([$this->tracker_v3]);

        $this->project_history_dao->expects(self::once())->method('groupAddHistory');
        $this->project_history_dao->expects(self::once())->method('addHistory');
        $this->event_manager->expects(self::exactly(2))->method('processEvent');
        $this->event_manager->expects(self::once())->method('dispatch');

        $this->remover->forceRemoveAdminRestrictedUserFromProject($project, $user);
    }

    public function testItDoesNotForceRemovalOfUserFromProjectAdminAndUgroupsIfNotRestricted(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::anActiveUser()->withId(102)->build();

        $this->user_permissions_dao->expects(self::never())->method('removeUserFromProjectAdmin');
        $this->dao->expects(self::never())->method('removeNonAdminUserFromProject');
        $this->dao->expects(self::never())->method('removeUserFromProjectUgroups');
        $this->project_history_dao->expects(self::never())->method('groupAddHistory');
        $this->tracker_v3->expects(self::never())->method('deleteUser');
        $this->event_manager->expects(self::never())->method('processEvent');
        $this->event_manager->expects(self::never())->method('dispatch');

        $this->remover->forceRemoveAdminRestrictedUserFromProject($project, $user);
    }

    public function testItDoesNothingIfTheUserIsNotRemovedFromProjectMembers(): void
    {
        $project_id = 101;
        $user_id    = 102;

        $this->project_manager->method('getProject')->with(101)->willReturn($this->project);

        $this->dao->expects(self::once())->method('removeNonAdminUserFromProject');
        $this->dao->expects(self::never())->method('removeUserFromProjectUgroups');
        $this->project_history_dao->expects(self::never())->method('groupAddHistory');
        $this->tracker_v3->expects(self::never())->method('deleteUser');
        $this->event_manager->expects(self::never())->method('processEvent');

        $this->remover->removeUserFromProject($project_id, $user_id);
    }
}
