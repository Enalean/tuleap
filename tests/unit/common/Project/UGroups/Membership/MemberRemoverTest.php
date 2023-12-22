<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Membership;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;
use Tuleap\Test\Builders\UserTestBuilder;

final class MemberRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private DynamicUGroupMembersUpdater&MockObject $dynamic_ugroup_members_updater;
    private StaticMemberRemover&MockObject $static_member_remover;
    private MemberRemover $member_remover;
    private \PFUser $user_to_remove;
    private \PFUser $project_administrator;

    protected function setUp(): void
    {
        $this->dynamic_ugroup_members_updater = $this->createMock(DynamicUGroupMembersUpdater::class);
        $this->static_member_remover          = $this->createMock(StaticMemberRemover::class);
        $this->user_to_remove                 = UserTestBuilder::aUser()->withId(303)->build();
        $this->project_administrator          = UserTestBuilder::aUser()->withId(158)->build();
        $this->member_remover                 = new MemberRemover($this->dynamic_ugroup_members_updater, $this->static_member_remover);
    }

    public function testItRemovesFromDynamicUGroup(): void
    {
        $project = new \Project(['group_id' => 101]);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('getId')->willReturn(202);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('isStatic')->willReturn(false);

        $this->static_member_remover->expects(self::never())->method('removeUser');
        $this->dynamic_ugroup_members_updater->method('removeUser')->with(
            $project,
            $ugroup,
            $this->user_to_remove,
            $this->project_administrator,
        );

        $this->member_remover->removeMember($this->user_to_remove, $this->project_administrator, $ugroup);
    }

    public function testItRemovesFromStaticUGroup(): void
    {
        $project = new \Project(['group_id' => 101]);

        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('getId')->willReturn(202);
        $ugroup->method('isBound')->willReturn(false);
        $ugroup->method('isStatic')->willReturn(true);

        $this->static_member_remover->method('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->expects(self::never())->method('removeUser');

        $this->member_remover->removeMember($this->user_to_remove, $this->project_administrator, $ugroup);
    }

    public function testItRemovesNothingFromBoundGroup(): void
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $ugroup->method('getId')->willReturn(202);
        $ugroup->method('isBound')->willReturn(true);

        $this->static_member_remover->expects(self::never())->method('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->expects(self::never())->method('removeUser');

        self::expectException(CannotModifyBoundGroupException::class);

        $this->member_remover->removeMember($this->user_to_remove, $this->project_administrator, $ugroup);
    }
}
