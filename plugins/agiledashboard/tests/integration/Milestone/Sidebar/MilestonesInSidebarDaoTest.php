<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class MilestonesInSidebarDaoTest extends TestIntegrationTestCase
{
    private MilestonesInSidebarDao $dao;
    private const ACME_PROJECT_ID                = 1;
    private const DUNDER_MIFFLIN_PROJECT_ID      = 2;
    private const SKYNET_PROJECT_ID              = 3;
    private const LOS_POLLOS_HERMANOS_PROJECT_ID = 4;

    protected function setUp(): void
    {
        $this->dao = new MilestonesInSidebarDao();
    }

    public function tearDown(): void
    {
        \ForgeConfig::clearFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG);
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagIsNotSet(): void
    {
        $this->dao->deactivateMilestonesInSidebar(self::ACME_PROJECT_ID);
        $this->dao->activateMilestonesInSidebar(self::DUNDER_MIFFLIN_PROJECT_ID);

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }

    public function testDuplicateWhenDeactivated(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '1');
        $this->dao->deactivateMilestonesInSidebar(self::ACME_PROJECT_ID);

        $this->dao->duplicate(self::LOS_POLLOS_HERMANOS_PROJECT_ID, self::ACME_PROJECT_ID);
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::LOS_POLLOS_HERMANOS_PROJECT_ID));
    }

    public function testDuplicateWhenActivated(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '1');
        $this->dao->activateMilestonesInSidebar(self::DUNDER_MIFFLIN_PROJECT_ID);

        $this->dao->duplicate(self::LOS_POLLOS_HERMANOS_PROJECT_ID, self::DUNDER_MIFFLIN_PROJECT_ID);
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::LOS_POLLOS_HERMANOS_PROJECT_ID));
    }

    public function testDuplicateWhenNoExplicitChoice(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '1');
        $this->dao->duplicate(self::LOS_POLLOS_HERMANOS_PROJECT_ID, self::SKYNET_PROJECT_ID);
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::LOS_POLLOS_HERMANOS_PROJECT_ID));
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagAllowsMilestonesInSidebar(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '1');
        $this->dao->deactivateMilestonesInSidebar(self::ACME_PROJECT_ID);
        $this->dao->activateMilestonesInSidebar(self::DUNDER_MIFFLIN_PROJECT_ID);

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagForbidsMilestonesInSidebar(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '0');
        $this->dao->deactivateMilestonesInSidebar(self::ACME_PROJECT_ID);
        $this->dao->activateMilestonesInSidebar(self::DUNDER_MIFFLIN_PROJECT_ID);

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }

    public function testShouldActivateOrDeactivateAnExistingEntry(): void
    {
        $this->dao->activateMilestonesInSidebar(self::ACME_PROJECT_ID);
        $this->dao->deactivateMilestonesInSidebar(self::ACME_PROJECT_ID);
        $this->dao->activateMilestonesInSidebar(self::ACME_PROJECT_ID);

        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
    }
}
