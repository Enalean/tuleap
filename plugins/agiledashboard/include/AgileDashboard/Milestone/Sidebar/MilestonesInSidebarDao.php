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

use Override;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\DB\DataAccessObject;

final class MilestonesInSidebarDao extends DataAccessObject implements CheckMilestonesInSidebar, DuplicateMilestonesInSidebarConfig, UpdateMilestonesInSidebarConfig
{
    #[FeatureFlagConfigKey('Allow milestones in sidebar. 0 to disallow, 1 to allow. By default they are allowed. Guarded by allow_milestones_in_sidebar_dev_mode feature flag.')]
    #[ConfigKeyInt(1)]
    public const string FEATURE_FLAG = 'allow_milestones_in_sidebar';

    private const SHOULD_SIDEBAR_DISPLAY_LAST_MILESTONES_WHEN_NO_CONFIG = true;

    #[Override]
    public function shouldSidebarDisplayLastMilestones(int $project_id): bool
    {
        $feature_flag = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG);
        if ($feature_flag !== false && (int) $feature_flag === 0) {
            return false;
        }

        $config = $this->getDB()->cell(
            <<<EOSQL
            SELECT should_sidebar_display_last_milestones
            FROM plugin_agiledashboard_milestones_in_sidebar_config
            WHERE project_id = ?
            EOSQL,
            $project_id
        );
        if ($config === false) {
            return self::SHOULD_SIDEBAR_DISPLAY_LAST_MILESTONES_WHEN_NO_CONFIG;
        }

        return $config === 1;
    }

    #[Override]
    public function duplicate(int $target_project_id, int $source_project_id): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_agiledashboard_milestones_in_sidebar_config(project_id, should_sidebar_display_last_milestones)
        SELECT ?, should_sidebar_display_last_milestones
        FROM plugin_agiledashboard_milestones_in_sidebar_config
        WHERE project_id = ?
        EOSQL;
        $this->getDB()->run($sql, $target_project_id, $source_project_id);
    }

    #[Override]
    public function activateMilestonesInSidebar(int $project_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_agiledashboard_milestones_in_sidebar_config(project_id, should_sidebar_display_last_milestones)
                SELECT ?, 1
                ON DUPLICATE KEY UPDATE should_sidebar_display_last_milestones = 1
                EOSQL,
                $project_id
            );
    }

    #[Override]
    public function deactivateMilestonesInSidebar(int $project_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_agiledashboard_milestones_in_sidebar_config(project_id, should_sidebar_display_last_milestones)
                SELECT ?, 0
                ON DUPLICATE KEY UPDATE should_sidebar_display_last_milestones = 0
                EOSQL,
                $project_id
            );
    }
}
