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

use Mockery as M;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;
use Tuleap\Test\Builders\UserTestBuilder;

final class MemberRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var M\MockInterface|DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var M\MockInterface|StaticMemberRemover
     */
    private $static_member_remover;
    private MemberRemover $member_remover;
    private \PFUser $user_to_remove;
    private \PFUser $project_administrator;

    protected function setUp(): void
    {
        $this->dynamic_ugroup_members_updater = M::mock(DynamicUGroupMembersUpdater::class);
        $this->static_member_remover          = M::mock(StaticMemberRemover::class);
        $this->user_to_remove                 = UserTestBuilder::aUser()->withId(303)->build();
        $this->project_administrator          = UserTestBuilder::aUser()->withId(158)->build();
        $this->member_remover                 = new MemberRemover($this->dynamic_ugroup_members_updater, $this->static_member_remover);
    }

    public function testItRemovesFromDynamicUGroup(): void
    {
        $project = new \Project(['group_id' => 101]);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProject' => $project, 'getId' => 202, 'isBound' => false, 'isStatic' => false]);

        $this->static_member_remover->shouldNotReceive('removeUser');
        $this->dynamic_ugroup_members_updater->shouldReceive('removeUser')->with(
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

        $ugroup = M::mock(\ProjectUGroup::class, ['getProject' => $project, 'getId' => 202, 'isBound' => false, 'isStatic' => true]);

        $this->static_member_remover->shouldReceive('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->member_remover->removeMember($this->user_to_remove, $this->project_administrator, $ugroup);
    }

    public function testItRemovesNothingFromBoundGroup(): void
    {
        $ugroup = M::mock(\ProjectUGroup::class, ['getId' => 202, 'isBound' => true]);

        $this->static_member_remover->shouldNotReceive('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->expectException(CannotModifyBoundGroupException::class);

        $this->member_remover->removeMember($this->user_to_remove, $this->project_administrator, $ugroup);
    }
}
