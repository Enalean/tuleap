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
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;

class MemberRemoverTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var M\MockInterface|DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var M\MockInterface|StaticMemberRemover
     */
    private $static_member_remover;
    /**
     * @var MemberRemover
     */
    private $member_remover;
    /**
     * @var \PFUser
     */
    private $user_to_remove;

    protected function setUp(): void
    {
        $this->dynamic_ugroup_members_updater = M::mock(DynamicUGroupMembersUpdater::class);
        $this->static_member_remover          = M::mock(StaticMemberRemover::class);
        $this->user_to_remove                 = new \PFUser(['user_id' => 303]);
        $this->member_remover                 = new MemberRemover($this->dynamic_ugroup_members_updater, $this->static_member_remover);
    }

    public function testItRemovesFromDynamicUGroup()
    {
        $project = new \Project(['group_id' => 101]);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProject' => $project, 'getId' => 202, 'isBound' => false, 'isStatic' => false]);

        $this->static_member_remover->shouldNotReceive('removeUser');
        $this->dynamic_ugroup_members_updater->shouldReceive('removeUser')->with($project, $ugroup, $this->user_to_remove);

        $this->member_remover->removeMember($this->user_to_remove, $ugroup);
    }

    public function testItRemovesFromStaticUGroup()
    {
        $project = new \Project(['group_id' => 101]);

        $ugroup = M::mock(\ProjectUGroup::class, ['getProject' => $project, 'getId' => 202, 'isBound' => false, 'isStatic' => true]);

        $this->static_member_remover->shouldReceive('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->member_remover->removeMember($this->user_to_remove, $ugroup);
    }

    public function testItRemovesNothingFromBoundGroup()
    {
        $ugroup = M::mock(\ProjectUGroup::class, ['getId' => 202, 'isBound' => true]);

        $this->static_member_remover->shouldNotReceive('removeUser')->with($ugroup, $this->user_to_remove);
        $this->dynamic_ugroup_members_updater->shouldNotReceive('removeUser');

        $this->expectException(CannotModifyBoundGroupException::class);

        $this->member_remover->removeMember($this->user_to_remove, $ugroup);
    }
}
