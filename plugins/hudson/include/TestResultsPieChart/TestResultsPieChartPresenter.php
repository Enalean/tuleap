<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Hudson\TestResultPieChart;

class TestResultsPieChartPresenter
{
    /**
     * @var string
     */
    public $test_results = [];

    /**
     * @var bool
     */
    public $has_data_to_display;

    /**
     * @var string
     */
    public $latest_results_url;

    /**
     * @var string
     */
    public $pie_chart_id;

    public function __construct(
        $widget_id,
        $job_id,
        $group_id,
        $successful_tests,
        $skipped_tests,
        $failed_tests,
        $total_passed_tests
    ) {
        $this->has_data_to_display = $total_passed_tests > 0;

        if (! $this->has_data_to_display) {
            return;
        }

        $results = [];

        if ($successful_tests > 0) {
            $results[] = [
                "count" => $successful_tests,
                "key"   => "success",
                "value" => $this->getPercentage($successful_tests, $total_passed_tests),
                "label" => sprintf(
                    dgettext("tuleap-hudson", "Passed (%s)"),
                    $successful_tests
                )
            ];
        }

        if ($skipped_tests > 0) {
            $results[] = [
                "count" => $skipped_tests,
                "key"   => "skipped",
                "value" => $this->getPercentage($skipped_tests, $total_passed_tests),
                "label" => sprintf(
                    dgettext("tuleap-hudson", "Skipped (%s)"),
                    $skipped_tests
                )
            ];
        }

        if ($failed_tests > 0) {
            $results[] = [
                "count" => $failed_tests,
                "key"   => "failed",
                "value" => $this->getPercentage($failed_tests, $total_passed_tests),
                "label" => sprintf(
                    dgettext("tuleap-hudson", "Failed (%s)"),
                    $failed_tests
                )
            ];
        }

        $this->test_results = json_encode($results);

        $this->latest_results_url = HUDSON_BASE_URL . '?' . http_build_query([
            "action"   => "view_last_test_result",
            "group_id" => $group_id,
            "job_id"   => $job_id
        ]);

        $this->pie_chart_id = 'test-results-pie-' . $widget_id;
    }

    private function getPercentage($nb_tests, $total_passed_tests)
    {
        return sprintf(
            '%01.1f%%',
            ($nb_tests / $total_passed_tests) * 100
        );
    }
}
