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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;

final class ProjectImportCleanupUserCreatorFromAdministratorsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testValuesHoldByTheEventCanBeAccessed(): void
    {
        $creator = Mockery::mock(PFUser::class);
        $ugroup  = Mockery::mock(ProjectUGroup::class);
        $ugroup->shouldReceive('getId')->andReturn(ProjectUGroup::PROJECT_ADMIN);
        $event = new ProjectImportCleanupUserCreatorFromAdministrators($creator, $ugroup);

        $this->assertSame($creator, $event->getCreator());
        $this->assertSame($ugroup, $event->getUGroupAdministrator());
    }

    public function testOnlyProjectAdminUGroupAreAccepted(): void
    {
        $ugroup  = Mockery::mock(ProjectUGroup::class);
        $ugroup->shouldReceive('getId')->andReturn(ProjectUGroup::PROJECT_MEMBERS);

        $this->expectException(NotProjectAdministratorUGroup::class);
        new ProjectImportCleanupUserCreatorFromAdministrators(Mockery::mock(PFUser::class), $ugroup);
    }
}
