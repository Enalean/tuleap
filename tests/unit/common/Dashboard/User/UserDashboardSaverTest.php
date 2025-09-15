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

namespace Tuleap\Dashboard\User;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Stubs\Dashboard\User\SearchByUserIdAndNameStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserDashboardSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserDashboardDao&MockObject $dao;
    private \PFUser $user;
    private UserDashboardSaver $user_saver;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao  = $this->createMock(\Tuleap\Dashboard\User\UserDashboardDao::class);
        $this->user = new \PFUser(['user_id' => 1, 'language_id' => 'en_US']);

        $this->user_saver = new UserDashboardSaver(
            SearchByUserIdAndNameStub::withDashboards(
                [
                    'id'      => 1,
                    'user_id' => 1,
                    'name'    => 'existing_dashboard',
                ]
            ),
            $this->dao
        );
    }

    public function testItSavesDashboard(): void
    {
        $this->dao->expects($this->once())->method('save')->with($this->user, 'new_dashboard');

        $this->user_saver->save($this->user, 'new_dashboard');
    }

    public function testItThrowsExceptionWhenDashboardExists(): void
    {
        $this->dao->expects($this->never())->method('save');
        $this->expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->user_saver->save($this->user, 'existing_dashboard');
    }

    public function testItThrowsExceptionWhenNameDoesNotExist(): void
    {
        $this->dao->expects($this->never())->method('save');
        $this->expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->user_saver->save($this->user, '');
    }
}
