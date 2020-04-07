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

final class BoundUGroupRefresherTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

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

    protected function setUp(): void
    {
        $this->ugroup_user_dao = Mockery::mock(\UGroupUserDao::class);
        $this->ugroup_manager  = Mockery::mock(\UGroupManager::class);
        $this->refresher       = new BoundUGroupRefresher($this->ugroup_manager, $this->ugroup_user_dao);

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
            ->with('warning', 'Error message')
            ->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error message');

        $this->refresher->refresh($source, $destination);
    }

    public function testRefreshClearsAndDuplicatesUGroupMembers(): void
    {
        $source      = Mockery::mock(\ProjectUGroup::class, ['getId' => 149]);
        $destination = Mockery::mock(\ProjectUGroup::class, ['getId' => 371]);

        $this->ugroup_user_dao->shouldReceive('resetUgroupUserList')
            ->with(371)
            ->once()
            ->andReturnTrue();
        $this->ugroup_user_dao->shouldReceive('cloneUgroup')
            ->with(149, 371)
            ->once()
            ->andReturnTrue();

        $this->refresher->refresh($source, $destination);
    }
}
