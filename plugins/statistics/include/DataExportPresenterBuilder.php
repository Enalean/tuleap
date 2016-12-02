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

use DateInterval;
use DateTime;

class DataExportPresenterBuilder
{
    public function build(
        $title,
        $services_usage_start_date,
        $services_usage_end_date,
        $scm_statistics_start_date,
        $scm_statistics_end_date,
        $scm_statistics_selected_project
    ) {
        if (! $services_usage_start_date) {
            $services_usage_start_date_time = new DateTime();
            $services_usage_start_date = $services_usage_start_date_time->sub(new DateInterval('P1M'))->format('Y-m-d');
        }
        if (! $services_usage_end_date) {
            $services_usage_end_date_time = new DateTime();
            $services_usage_end_date = $services_usage_end_date_time->format('Y-m-d');
        }
        if (! $scm_statistics_start_date) {
            $scm_statistics_start_date_time = new DateTime();
            $scm_statistics_start_date = $scm_statistics_start_date_time->sub(new DateInterval('P1Y'))->format('Y-m-d');
        }
        if (! $scm_statistics_end_date) {
            $scm_statistics_end_date_time = new DateTime();
            $scm_statistics_end_date = $scm_statistics_end_date_time->format('Y-m-d');
        }

        if ($scm_statistics_start_date > $scm_statistics_end_date || $services_usage_start_date > $scm_statistics_end_date) {
            throw new StartDateGreaterThanEndDateException();
        }

        $header_presenter = new AdminHeaderPresenter(
            $title,
            'data_export'
        );

        $usage_progress_presenter = new UsageProgressPresenter();
        $services_usage_presenter = new ServicesUsagePresenter(
            $services_usage_start_date,
            $services_usage_end_date
        );
        $scm_statistics_presenter = new SCMStatisticsPresenter(
            $scm_statistics_selected_project,
            $scm_statistics_start_date,
            $scm_statistics_end_date
        );

        return new DataExportPresenter(
            $header_presenter,
            $usage_progress_presenter,
            $services_usage_presenter,
            $scm_statistics_presenter
        );
    }
}
