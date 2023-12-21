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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

final class BoundUGroupRefresherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private BoundUGroupRefresher $refresher;
    private \UGroupUserDao&MockObject $ugroup_user_dao;
    private \UGroupManager&MockObject $ugroup_manager;

    protected function setUp(): void
    {
        $this->ugroup_user_dao = $this->createMock(\UGroupUserDao::class);
        $this->ugroup_manager  = $this->createMock(\UGroupManager::class);
        $this->refresher       = new BoundUGroupRefresher($this->ugroup_manager, $this->ugroup_user_dao);
    }

    public function testRefreshThrowsWhenUpdateOfMembersIsNotAllowed(): void
    {
        $source      = ProjectUGroupTestBuilder::aCustomUserGroup(149)->build();
        $destination = ProjectUGroupTestBuilder::aCustomUserGroup(371)->build();

        $this->ugroup_manager
            ->expects(self::once())
            ->method('isUpdateUsersAllowed')
            ->with(371)
            ->willReturn(false);

        $GLOBALS['Language']->method('getText')
            ->willReturn('Error message');

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')
            ->with('warning', 'Error message');

        self::expectException(\Exception::class);
        self::expectExceptionMessage('Error message');

        $this->refresher->refresh($source, $destination);
    }

    public function testRefreshClearsAndDuplicatesUGroupMembers(): void
    {
        $source      = ProjectUGroupTestBuilder::aCustomUserGroup(149)->build();
        $destination = ProjectUGroupTestBuilder::aCustomUserGroup(371)->build();

        $this->ugroup_manager
            ->expects(self::once())
            ->method('isUpdateUsersAllowed')
            ->with(371)
            ->willReturn(true);
        $this->ugroup_user_dao
            ->expects(self::once())
            ->method('resetUgroupUserList')
            ->with(371)
            ->willReturn(true);
        $this->ugroup_user_dao
            ->expects(self::once())
            ->method('cloneUgroup')
            ->with(149, 371)
            ->willReturn(true);

        $this->refresher->refresh($source, $destination);
    }
}
