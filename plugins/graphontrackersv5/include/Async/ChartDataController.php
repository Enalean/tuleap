<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\Async;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\GraphOnTrackersV5\DataAccess\GraphOnTrackersV5_ChartFactory;
use Tuleap\GraphOnTrackersV5\DataTransformation\ChartFieldNotFoundException;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;

final class ChartDataController extends DispatchablePSR15Compatible
{
    /** @var \Tracker_ReportFactory */
    private $report_factory;
    /** @var \Tracker_Report_RendererFactory */
    private $renderer_factory;
    /** @var \GraphOnTrackersV5_ChartFactory */
    private $chart_factory;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var JSONResponseBuilder
     */
    private $json_response_builder;

    public function __construct(
        \Tracker_ReportFactory $report_factory,
        \Tracker_Report_RendererFactory $renderer_factory,
        GraphOnTrackersV5_ChartFactory $chart_factory,
        \UserManager $user_manager,
        JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->report_factory        = $report_factory;
        $this->renderer_factory      = $renderer_factory;
        $this->chart_factory         = $chart_factory;
        $this->user_manager          = $user_manager;
        $this->json_response_builder = $json_response_builder;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $report_id    = $request->getAttribute('report_id');
        $renderer_id  = $request->getAttribute('renderer_id');
        $chart_id     = $request->getAttribute('chart_id');
        $params       = $request->getQueryParams();
        $in_dashboard = $params['in_dashboard'] ?? false;

        $current_user = $this->user_manager->getCurrentUser();

        $report = $this->report_factory->getReportById($report_id, $current_user->getId());
        if ($report === null) {
            return $this->createNotFoundErrorResponse(dgettext('tuleap-graphontrackersv5', 'Report not found.'));
        }

        $renderer = $this->renderer_factory->getReportRendererByReportAndId($report, $renderer_id);
        if ($renderer === null) {
            return $this->createNotFoundErrorResponse(dgettext('tuleap-graphontrackersv5', 'Renderer not found.'));
        }

        $chart = $this->chart_factory->getChart($renderer, $chart_id);
        if ($chart === null) {
            return $this->createNotFoundErrorResponse(dgettext('tuleap-graphontrackersv5', 'Chart not found.'));
        }

        try {
            $chart_data = $chart->fetchAsArray((bool) $in_dashboard);
            if ($chart_data) {
                return $this->json_response_builder->fromData($chart_data);
            } else {
                return $this->createNotFoundErrorResponse(
                    dgettext('tuleap-graphontrackersv5', 'No data to display for graph')
                );
            }
        } catch (ChartFieldNotFoundException $exception) {
            return $this->createNotFoundErrorResponse($exception->getMessage());
        }
    }

    private function createNotFoundErrorResponse(string $error_message): ResponseInterface
    {
        return $this->json_response_builder->fromData(['error_message' => $error_message])->withStatus(404);
    }
}
