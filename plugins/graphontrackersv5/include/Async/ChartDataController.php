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

namespace Tuleap\GraphOnTrackersV5\Async;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ChartDataController implements DispatchableWithRequest
{
    /** @var \Tracker_ReportFactory */
    private $report_factory;
    /** @var \Tracker_Report_RendererFactory */
    private $renderer_factory;
    /** @var \GraphOnTrackersV5_ChartFactory */
    private $chart_factory;

    public function __construct(
        \Tracker_ReportFactory $report_factory,
        \Tracker_Report_RendererFactory $renderer_factory,
        \GraphOnTrackersV5_ChartFactory $chart_factory
    ) {
        $this->report_factory   = $report_factory;
        $this->renderer_factory = $renderer_factory;
        $this->chart_factory    = $chart_factory;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $report_id   = $variables['report_id'];
        $renderer_id = $variables['renderer_id'];
        $chart_id    = $variables['chart_id'];

        $current_user = $request->getCurrentUser();

        $report = $this->report_factory->getReportById($report_id, $current_user->getId());
        if ($report === null) {
            throw new NotFoundException(dgettext('tuleap-graphontrackersv5', 'Report not found.'));
        }

        $renderer = $this->renderer_factory->getReportRendererByReportAndId($report, $renderer_id);
        if ($renderer === null) {
            throw new NotFoundException(dgettext('tuleap-graphontrackersv5', 'Renderer not found.'));
        }

        $chart = $this->chart_factory->getChart($renderer, $chart_id);
        if ($chart === null) {
            throw new NotFoundException(dgettext('tuleap-graphontrackersv5', 'Chart not found.'));
        }

        header('Content-type: application/json');
        echo json_encode($chart->fetchAsArray());
    }
}
