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
use Tuleap\Layout\IncludeAssets;

class DiskUsagePieDisplayer
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_manager;
    /**
     * @var ProjectQuotaManager
     */
    private $quota_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $disk_usage_output;

    public function __construct(
        Statistics_DiskUsageManager $disk_manager,
        ProjectQuotaManager $quota_manager,
        Statistics_DiskUsageOutput $disk_usage_output
    ) {
        $this->disk_manager      = $disk_manager;
        $this->quota_manager     = $quota_manager;
        $this->disk_usage_output = $disk_usage_output;
    }

    public function displayDiskUsagePie(Project $project)
    {
        $project_id      = $project->getID();
        $quota           = $this->disk_manager->getProperty('allowed_quota');
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

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/statistics',
            '/assets/statistics'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile(
            $include_assets->getFileURL('disk-usage-pie.js')
        );

        $renderer->renderToPage(
            'disk-usage-pie-mount-point',
            $presenter
        );
    }
}
