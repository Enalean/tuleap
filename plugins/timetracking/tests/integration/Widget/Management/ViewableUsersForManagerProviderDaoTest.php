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
use Tuleap\Test\Stubs\ProvideUserFromRowStub;

#[DisableReturnValueGenerationForTestDoubles]
final class ViewableUsersForManagerProviderDaoTest extends ManagerPermissionsTestBase
{
    public function testWhenTimetrackingIsDisabled(): void
    {
        $this->disableTimetrackingForTrackers();

        foreach ($this->managers as $manager) {
            $this->assertManagerCannotSeeAlice($manager);
        }
    }

    public function testWhenTimetrackingIsEnabled(): void
    {
        $this->enableTimetrackingForTrackers();

        $this->assertManagerCanSeeAlice($this->bob);
        $this->assertManagerCanSeeAlice($this->charlie);
        $this->assertManagerCanSeeAlice($this->dylan);
        $this->assertManagerCannotSeeAlice($this->eleonor);
        $this->assertManagerCannotSeeAlice($this->frank);
        $this->assertManagerCannotSeeAlice($this->gaston);
        $this->assertManagerCanSeeAlice($this->hector);
        $this->assertManagerCanSeeAlice($this->igor);
        $this->assertManagerCanSeeAlice($this->june);
        $this->assertManagerCanSeeAlice($this->kevin);
    }

    private function assertManagerCanSeeAlice(\PFUser $manager): void
    {
        $dao = new ViewableUsersForManagerProviderDao(ProvideUserFromRowStub::build());

        $result = $dao->getPaginatedViewableUsersForManager($manager, 'alice', 0, 10)->getUsers();
        self::assertCount(1, $result);
        self::assertSame((int) $this->alice->getId(), (int) $result[0]->getId());
    }

    private function assertManagerCannotSeeAlice(\PFUser $manager): void
    {
        $dao = new ViewableUsersForManagerProviderDao(ProvideUserFromRowStub::build());

        self::assertCount(0, $dao->getPaginatedViewableUsersForManager($manager, 'alice', 0, 10)->getUsers());
    }
}
