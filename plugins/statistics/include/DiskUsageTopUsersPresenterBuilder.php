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

class DiskUsageTopUsersPresenterBuilder
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_usage_manager;
    /**
     * @var DiskUsageOutput
     */
    private $disk_usage_output;

    public function __construct(
        Statistics_DiskUsageManager $disk_usage_manager,
        Statistics_DiskUsageOutput $disk_usage_output
    ) {
        $this->disk_usage_manager = $disk_usage_manager;
        $this->disk_usage_output  = $disk_usage_output;
    }

    public function build(
        $title,
        $end_date
    ) {
        if (! $end_date) {
            $end_date = date('Y-m-d');
        }

        $order = 'end_size';

        $users = $this->disk_usage_manager->getTopUsers($end_date, $order);

        $data_top_users = [];
        $rank = 0;
        foreach ($users as $row) {
            $user_details_query = http_build_query([
                'user_id' => $row['user_id']
            ]);

            $disk_usage_user_details_query = http_build_query([
                'menu'     => 'one_user_details',
                'end_date' => $end_date,
                'user'     => $row['user_name']
            ]);

            $data_user = [
                'rank'                        => $rank + 1,
                'user_name'                   => $row['user_name'],
                'user_details_url'            => '/admin/usergroup.php?' . $user_details_query,
                'disk_usage_user_details_url' => 'disk_usage.php?' . $disk_usage_user_details_query,
                'end_size'                    => $this->disk_usage_output->sizeReadable($row['end_size'])
            ];

            $data_top_users[] = $data_user;
            $rank++;
        }

        $header_presenter = new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );
        return new DiskUsageTopUsersPresenter(
            $header_presenter,
            $end_date,
            $data_top_users
        );
    }
}
