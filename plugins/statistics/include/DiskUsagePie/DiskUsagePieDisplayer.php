<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Statistics\DiskUsagePie;

use Project;
use ProjectQuotaManager;
use Statistics_DiskUsageManager;
use Statistics_DiskUsageOutput;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeViteAssets;

final readonly class DiskUsagePieDisplayer
{
    public function __construct(
        private Statistics_DiskUsageManager $disk_manager,
        private ProjectQuotaManager $quota_manager,
        private Statistics_DiskUsageOutput $disk_usage_output,
    ) {
    }

    public function displayDiskUsagePie(Project $project): void
    {
        $project_id      = $project->getID();
        $quota           = \ForgeConfig::getInt(ProjectQuotaManager::CONFIG_ALLOWED_QUOTA);
        $custom_quota    = $this->quota_manager->getProjectCustomQuota($project->getId());
        $used_proportion = $this->disk_manager->returnTotalProjectSize($project_id);

        if ($custom_quota) {
            $quota = $custom_quota;
        }

        $allowed_quota = $quota * (1024 * 1024 * 1024);

        if ($used_proportion > $allowed_quota) {
            $used_proportion = $allowed_quota;
            $remaining_space = 0;
        } else {
            $remaining_space = $allowed_quota - $used_proportion;
        }

        $human_readable_remaining_space = $this->disk_usage_output->sizeReadable($remaining_space);
        $human_readable_usage           = $this->disk_usage_output->sizeReadable($used_proportion);

        $presenter = new DiskUsagePieMountPointPresenter(
            $remaining_space,
            $used_proportion,
            $human_readable_remaining_space,
            $human_readable_usage
        );
        $renderer  = TemplateRendererFactory::build()->getRenderer(STATISTICS_TEMPLATE_DIR);

        $include_assets = new IncludeViteAssets(
            __DIR__ . '/../../frontend-assets',
            '/assets/statistics'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile(
            $include_assets->getFileURL('scripts/disk-usage-pie/src/disk-usage-pie-chart.js')
        );

        $renderer->renderToPage(
            'disk-usage-pie-mount-point',
            $presenter
        );
    }
}
