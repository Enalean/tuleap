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

use ProjectUGroup;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectImportCleanupUserCreatorFromAdministratorsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testValuesHoldByTheEventCanBeAccessed(): void
    {
        $creator = UserTestBuilder::buildWithDefaults();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getId')->willReturn(ProjectUGroup::PROJECT_ADMIN);
        $event = new ProjectImportCleanupUserCreatorFromAdministrators($creator, $ugroup);

        self::assertSame($creator, $event->getCreator());
        self::assertSame($ugroup, $event->getUGroupAdministrator());
    }

    public function testOnlyProjectAdminUGroupAreAccepted(): void
    {
        $ugroup = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getId')->willReturn(ProjectUGroup::PROJECT_MEMBERS);

        self::expectException(NotProjectAdministratorUGroup::class);
        new ProjectImportCleanupUserCreatorFromAdministrators(UserTestBuilder::buildWithDefaults(), $ugroup);
    }
}
