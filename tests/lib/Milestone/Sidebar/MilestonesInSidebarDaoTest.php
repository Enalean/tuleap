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

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class MilestonesInSidebarDaoTest extends TestCase
{
    private const ACME_PROJECT_ID           = 1;
    private const DUNDER_MIFFLIN_PROJECT_ID = 2;
    private const SKYNET_PROJECT_ID         = 3;

    protected function setUp(): void
    {
        $this->dao = new MilestonesInSidebarDao();
    }

    public function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->query('TRUNCATE TABLE plugin_agiledashboard_milestones_in_sidebar_config');
        $db->query('DELETE FROM forgeconfig where name = "feature_flag_allow_milestones_in_sidebar"');
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagIsNotSet(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::ACME_PROJECT_ID, 'should_sidebar_display_last_milestones' => 0],
        );
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::DUNDER_MIFFLIN_PROJECT_ID, 'should_sidebar_display_last_milestones' => 1],
        );

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagAllowsMilestonesInSidebar(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '1');
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::ACME_PROJECT_ID, 'should_sidebar_display_last_milestones' => 0],
        );
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::DUNDER_MIFFLIN_PROJECT_ID, 'should_sidebar_display_last_milestones' => 1],
        );

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertTrue($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }

    public function testShouldSidebarDisplayLastMilestonesWhenFeatureFlagForbidsMilestonesInSidebar(): void
    {
        \ForgeConfig::setFeatureFlag(MilestonesInSidebarDao::FEATURE_FLAG, '0');
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::ACME_PROJECT_ID, 'should_sidebar_display_last_milestones' => 0],
        );
        $db->insert(
            'plugin_agiledashboard_milestones_in_sidebar_config',
            ['project_id' => self::DUNDER_MIFFLIN_PROJECT_ID, 'should_sidebar_display_last_milestones' => 1],
        );

        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::ACME_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::DUNDER_MIFFLIN_PROJECT_ID));
        self::assertFalse($this->dao->shouldSidebarDisplayLastMilestones(self::SKYNET_PROJECT_ID));
    }
}
