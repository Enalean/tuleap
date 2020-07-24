<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Statistics;

use Statistics_DiskUsageManager;
use Statistics_DiskUsageOutput;

class DiskUsageGlobalPresenterBuilder
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $usage_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $usage_output;

    public function __construct(
        Statistics_DiskUsageManager $usage_manager,
        Statistics_DiskUsageOutput $usage_output
    ) {
        $this->usage_manager = $usage_manager;
        $this->usage_output  = $usage_output;
    }

    public function build($title)
    {
        list($data_global, $date) = $this->buildDataGlobal();

        return new DiskUsageGlobalPresenter(
            $this->getHeaderPresenter($title),
            $data_global,
            $date
        );
    }

    private function getHeaderPresenter($title)
    {
        return new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );
    }

    private function buildDataGlobal()
    {
        $data_global = [];
        $result        = $this->usage_manager->getLatestData();

        if (isset($result['service'][Statistics_DiskUsageManager::USR_HOME])) {
            $data_global[] = [
                'title' => dgettext('tuleap-statistics', 'Users'),
                'size'  => $this->usage_output->sizeReadable($result['service'][Statistics_DiskUsageManager::USR_HOME])
            ];
        }
        if (isset($result['service'][Statistics_DiskUsageManager::MYSQL])) {
            $data_global[] = [
                'title' => 'MySQL',
                'size'  => $this->usage_output->sizeReadable($result['service'][Statistics_DiskUsageManager::MYSQL])
            ];
        }
        if (isset($result['service'][Statistics_DiskUsageManager::CODENDI_LOGS])) {
            $data_global[] = [
                'title' => dgettext('tuleap-statistics', 'Codendi Logs'),
                'size'  => $this->usage_output->sizeReadable($result['service'][Statistics_DiskUsageManager::CODENDI_LOGS])
            ];
        }
        if (isset($result['service'][Statistics_DiskUsageManager::BACKUP])) {
            $data_global[] = [
                'title' => dgettext('tuleap-statistics', 'Backup'),
                'size'  => $this->usage_output->sizeReadable($result['service'][Statistics_DiskUsageManager::BACKUP])
            ];
        }
        if (isset($result['service'][Statistics_DiskUsageManager::BACKUP_OLD])) {
            $data_global[] = [
                'title' => dgettext('tuleap-statistics', 'Backup old'),
                'size'  => $this->usage_output->sizeReadable($result['service'][Statistics_DiskUsageManager::BACKUP_OLD])
            ];
        }

        if (isset($result['path'])) {
            foreach ($result['path'] as $path => $size) {
                $data_global[] = [
                    'title' => $path,
                    'size'  => $this->usage_output->sizeReadable($size)
                ];
            }
        }

        return [$data_global, $result['date']];
    }
}
