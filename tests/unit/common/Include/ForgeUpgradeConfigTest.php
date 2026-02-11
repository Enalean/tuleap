<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

final class ForgeUpgradeConfigTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    public function testItRecordsOnlyThePathOfThePlugin(): void
    {
        $forge_upgrade = $this->createMock(\Tuleap\ForgeUpgrade\ForgeUpgrade::class);
        $forge_upgrade->expects($this->once())->method('recordOnlyPlugin')->with('/usr/share/tuleap/plugins/agiledashboard');

        $forgeupgrade_config = new ForgeUpgradeConfig($forge_upgrade);

        $forgeupgrade_config->recordOnlyPath('/usr/share/tuleap/plugins/agiledashboard');
    }

    public function testItCallsForgeUpgrade()
    {
        $forge_upgrade = $this->createMock(\Tuleap\ForgeUpgrade\ForgeUpgrade::class);
        $forge_upgrade->expects($this->once())->method('isSystemUpToDate');

        $forgeupgrade_config = new ForgeUpgradeConfig($forge_upgrade);

        $forgeupgrade_config->isSystemUpToDate();
    }

    public function testItReturnsTrueWhenForgeUpgradeTellsThatSystemIsUpToDate(): void
    {
        $forge_upgrade = $this->createStub(\Tuleap\ForgeUpgrade\ForgeUpgrade::class);
        $forge_upgrade->method('isSystemUpToDate')->willReturn(true);

        $forgeupgrade_config = new ForgeUpgradeConfig($forge_upgrade);

        self::assertTrue($forgeupgrade_config->isSystemUpToDate());
    }

    public function testItReturnsFalseWhenForgeUpgradeTellsThereArePendingBuckets(): void
    {
        $forge_upgrade = $this->createStub(\Tuleap\ForgeUpgrade\ForgeUpgrade::class);
        $forge_upgrade->method('isSystemUpToDate')->willReturn(false);

        $forgeupgrade_config = new ForgeUpgradeConfig($forge_upgrade);

        self::assertFalse($forgeupgrade_config->isSystemUpToDate());
    }
}
