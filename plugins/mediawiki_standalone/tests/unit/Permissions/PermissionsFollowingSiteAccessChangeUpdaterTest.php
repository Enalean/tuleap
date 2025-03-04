<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsFollowingSiteAccessChangeUpdaterTest extends TestCase
{
    public function testItUpdatesAllAnonymousAccessToRegisteredWhenPlatformWasAllowingAnonymous(): void
    {
        $dao = IUpdatePermissionsFollowingSiteAccessChangeStub::buildSelf();

        $updater = new PermissionsFollowingSiteAccessChangeUpdater($dao);

        $updater->updatePermissionsFollowingSiteAccessChange(\ForgeAccess::ANONYMOUS);

        self::assertTrue($dao->hasConversionFromAnonToRegisteredBeCalled());
        self::assertFalse($dao->hasConversionFromAuthenticatedToRegisteredBeCalled());
    }

    public function testItUpdatesAllAuthenticatedAccessToRegisteredWhenPlatformWasAllowingRestricted(): void
    {
        $dao = IUpdatePermissionsFollowingSiteAccessChangeStub::buildSelf();

        $updater = new PermissionsFollowingSiteAccessChangeUpdater($dao);

        $updater->updatePermissionsFollowingSiteAccessChange(\ForgeAccess::RESTRICTED);

        self::assertTrue($dao->hasConversionFromAuthenticatedToRegisteredBeCalled());
        self::assertFalse($dao->hasConversionFromAnonToRegisteredBeCalled());
    }

    public function testItDoesNotNeedToDoAnythingWhenPlatformAccessWasRegular(): void
    {
        $dao = IUpdatePermissionsFollowingSiteAccessChangeStub::buildSelf();

        $updater = new PermissionsFollowingSiteAccessChangeUpdater($dao);

        $updater->updatePermissionsFollowingSiteAccessChange(\ForgeAccess::REGULAR);

        self::assertFalse($dao->hasConversionFromAnonToRegisteredBeCalled());
        self::assertFalse($dao->hasConversionFromAuthenticatedToRegisteredBeCalled());
    }
}
