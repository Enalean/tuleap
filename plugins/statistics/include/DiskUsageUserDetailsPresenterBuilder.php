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
use UserManager;
use PFUser;
use DateTime;
use DateInterval;

class DiskUsageUserDetailsPresenterBuilder
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_usage_manager;
    /**
     * @var Statistics_DiskUsageOutput
     */
    private $disk_usage_output;
    /**
     * @var SearchFieldsPresenterBuilder
     */
    private $search_fields_builder;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        Statistics_DiskUsageManager $disk_usage_manager,
        Statistics_DiskUsageOutput $disk_usage_output,
        SearchFieldsPresenterBuilder $search_fields_builder,
        UserManager $user_manager
    ) {
        $this->disk_usage_manager    = $disk_usage_manager;
        $this->disk_usage_output     = $disk_usage_output;
        $this->search_fields_builder = $search_fields_builder;
        $this->user_manager          = $user_manager;
    }

    public function build(
        $title,
        $user_value,
        $group_by_value,
        $start_date_value,
        $end_date_value
    ) {
        if (! $start_date_value) {
            $start_date = new DateTime();
            $start_date_value = $start_date->sub(new DateInterval('P1W'))->format('Y-m-d');
        }

        if (! $end_date_value) {
            $end_date = new DateTime();
            $end_date_value = $end_date->format('Y-m-d');
        }

        if ($start_date_value > $end_date_value) {
            throw new StartDateGreaterThanEndDateException();
        }

        if (! $group_by_value) {
            $group_by_value = 'week';
        }

        $user              = $this->user_manager->findUser($user_value);
        $error_message     = false;
        $user_id           = false;
        $data_user_details = array();

        if (! $user) {
            $error_message = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'no_user_selected');
        } else {
            $user_id           = $user->getId();
            $data_user_details = $this->buildUserDetails($user, $start_date_value, $end_date_value);
            if (empty($data_user_details)) {
                $error_message = $GLOBALS['Language']->getText('plugin_statistics_admin_page', 'no_user_details_data');
            }
        }

        $search_fields = $this->search_fields_builder->buildSearchFieldsForUserDetails(
            $user_value,
            $group_by_value,
            $start_date_value,
            $end_date_value
        );

        $graph_url = $this->buildGraphParams(
            $user_id,
            $group_by_value,
            $start_date_value,
            $end_date_value
        );

        $header_presenter = new AdminHeaderPresenter(
            $title,
            'disk_usage'
        );
        return new DiskUsageUserDetailsPresenter(
            $header_presenter,
            $search_fields,
            $graph_url,
            $data_user_details,
            $error_message
        );
    }

    private function buildGraphParams(
        $user_id,
        $group_by,
        $start_date,
        $end_date
    ) {
        $graph_query = http_build_query(array(
            'user_id'    => $user_id,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'group_by'   => $group_by,
            'graph_type' => 'graph_user'
        ));

        return 'disk_usage_graph.php?' . $graph_query;
    }

    private function buildUserDetails(
        PFUser $user,
        $start_date_value,
        $end_date_value
    ) {
        $row = $this->disk_usage_manager->returnUserEvolutionForPeriod(
            $user->getId(),
            $start_date_value,
            $end_date_value
        );

        if (! $row) {
            return array();
        }

        $user_details_query = http_build_query(array(
            'user_id' => $user->getId()
        ));

        return array(
            'user_name'        => $user->getUserName(),
            'user_details_url' => '/admin/usergroup.php?' . $user_details_query,
            'start_size'       => $this->disk_usage_output->sizeReadable($row['start_size']),
            'end_size'         => $this->disk_usage_output->sizeReadable($row['end_size']),
            'evolution'        => $this->disk_usage_output->sizeReadable($row['evolution']),
            'evolution_rate'   => ($row['evolution'] == 0) ? '-' : sprintf('%01.2f %%', ($row['evolution_rate'] * 100))
        );
    }
}
