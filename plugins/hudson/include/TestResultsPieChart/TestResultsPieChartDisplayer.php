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

use HudsonTestResult;
use TemplateRendererFactory;

class TestResultsPieChartDisplayer
{
    public function displayTestResultsPieChart(
        $widget_id,
        $job_id,
        $group_id,
        HudsonTestResult $test_results
    ) {
        $presenter = new TestResultsPieChartPresenter(
            $widget_id,
            $job_id,
            $group_id,
            $test_results->getPassCount(),
            $test_results->getSkipCount(),
            $test_results->getFailCount(),
            $test_results->getTotalCount()
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(HUDSON_TEMPLATES_DIR);

        $renderer->renderToPage("test-results-pie-mount-point", $presenter);
    }
}
