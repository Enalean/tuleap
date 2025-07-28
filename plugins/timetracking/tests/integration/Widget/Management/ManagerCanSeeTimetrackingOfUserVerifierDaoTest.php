<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\Management;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;

#[DisableReturnValueGenerationForTestDoubles]
final class ManagerCanSeeTimetrackingOfUserVerifierDaoTest extends ManagerPermissionsTestBase
{
    public function testWhenTimetrackingIsDisabled(): void
    {
        $this->disableTimetrackingForTrackers();

        $dao = new ManagerCanSeeTimetrackingOfUserVerifierDao();

        foreach ($this->managers as $manager) {
            self::assertFalse($dao->isManagerAllowedToSeeTimetrackingOfUser($manager, $this->alice));
        }
    }

    public function testWhenTimetrackingIsEnabled(): void
    {
        $this->enableTimetrackingForTrackers();

        $dao = new ManagerCanSeeTimetrackingOfUserVerifierDao();

        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->bob, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->charlie, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->dylan, $this->alice));
        self::assertFalse($dao->isManagerAllowedToSeeTimetrackingOfUser($this->eleonor, $this->alice));
        self::assertFalse($dao->isManagerAllowedToSeeTimetrackingOfUser($this->frank, $this->alice));
        self::assertFalse($dao->isManagerAllowedToSeeTimetrackingOfUser($this->gaston, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->hector, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->igor, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->june, $this->alice));
        self::assertTrue($dao->isManagerAllowedToSeeTimetrackingOfUser($this->kevin, $this->alice));
    }
}
