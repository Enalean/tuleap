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
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Binding;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\MemberAdder;

final class BoundUGroupRefresherTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock, GlobalResponseMock;

    /** @var BoundUGroupRefresher */
    private $refresher;
    /**
     * @var Mockery\MockInterface|\UGroupUserDao
     */
    private $ugroup_user_dao;
    /**
     * @var Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var Mockery\MockInterface|MemberAdder
     */
    private $member_adder;

    protected function setUp(): void
    {
        $this->ugroup_user_dao = Mockery::mock(\UGroupUserDao::class);
        $this->ugroup_manager  = Mockery::mock(\UGroupManager::class);
        $this->member_adder    = Mockery::mock(MemberAdder::class);

        $this->refresher = new BoundUGroupRefresher(
            $this->ugroup_manager,
            $this->ugroup_user_dao,
            $this->member_adder
        );

        $this->ugroup_manager->shouldReceive('isUpdateUsersAllowed')
            ->with(371)
            ->andReturnTrue()
            ->byDefault();
    }

    public function testRefreshThrowsWhenUpdateOfMembersIsNotAllowed(): void
    {
        $source      = Mockery::mock(\ProjectUGroup::class, ['getId' => 149]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);

        $this->ugroup_manager->shouldReceive('isUpdateUsersAllowed')
            ->with(371)
            ->once()
            ->andReturnFalse();

        $GLOBALS['Language']->shouldReceive('getText')
            ->andReturn('Error message');

        $GLOBALS['Response']->shouldReceive('addFeedback')
            ->with(\Feedback::WARN, 'Error message')
            ->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error message');

        $this->refresher->refresh($source, $destination);
    }

    public function testRefreshClearsAndDuplicatesUGroupMembers(): void
    {
        $source        = Mockery::mock(\ProjectUGroup::class);
        $first_member  = Mockery::mock(\PFUser::class);
        $second_member = Mockery::mock(\PFUser::class);
        $source->shouldReceive('getMembers')
            ->once()
            ->andReturn([$first_member, $second_member]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);

        $this->ugroup_user_dao->shouldReceive('resetUgroupUserList')
            ->with(371)
            ->once()
            ->andReturnTrue();
        $this->member_adder->shouldReceive('addMember')
            ->with(Mockery::anyOf($first_member, $second_member), $destination);

        $this->refresher->refresh($source, $destination);
    }

    public function testRefreshAddsFeedbackWhenRestrictedUserCouldNotBeAdded(): void
    {
        $source            = Mockery::mock(\ProjectUGroup::class);
        $restricted_member = Mockery::mock(\PFUser::class, ['getId' => 200]);
        $normal_member     = Mockery::mock(\PFUser::class);
        $source->shouldReceive('getMembers')
            ->once()
            ->andReturn([$restricted_member, $normal_member]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);
        $destination->shouldReceive('getTranslatedName')
            ->once()
            ->andReturn('developpers');

        $this->ugroup_user_dao->shouldReceive('resetUgroupUserList')
            ->with(371)
            ->once()
            ->andReturnTrue();
        $project = Mockery::mock(\Project::class, ['getID' => 101]);
        $this->member_adder->shouldReceive('addMember')
            ->with($restricted_member, $destination)
            ->once()
            ->andThrow(
                new CannotAddRestrictedUserToProjectNotAllowingRestricted($restricted_member, $project)
            );
        $this->member_adder->shouldReceive('addMember')
            ->with($normal_member, $destination)
            ->once();
        $GLOBALS['Response']->shouldReceive('addFeedback')
            ->with(\Feedback::ERROR, Mockery::any())
            ->once();

        $this->refresher->refresh($source, $destination);
    }
}
