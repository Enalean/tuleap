<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

use Tracker;
use Tuleap\Tracker\Semantic\Timeframe\Events\DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;

class SemanticTimeframeAdministrationPresenterBuilder
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $tracker_formelement_factory;

    public function __construct(\Tracker_FormElementFactory $tracker_formelement_factory)
    {
        $this->tracker_formelement_factory = $tracker_formelement_factory;
    }

    public function build(
        \CSRFSynchronizerToken $csrf,
        Tracker $tracker,
        string $target_url,
        SemanticTimeframeCurrentConfigurationPresenter $configuration_presenter,
        IComputeTimeframes $timeframe
    ): SemanticTimeframeAdministrationPresenter {
        return new SemanticTimeframeAdministrationPresenter(
            $csrf,
            $tracker,
            $target_url,
            $this->doesTrackerHaveCharts($tracker),
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['date']),
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['int', 'float', 'computed']),
            $timeframe,
            $configuration_presenter
        );
    }

    private function doesTrackerHaveCharts(Tracker $tracker): bool
    {
        $event = new DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent($tracker);

        $chart_fields = $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, [
            'burnup',
            'burndown'
        ]);

        return count($chart_fields) > 0 || $event->doesAPluginRenderAChartForTracker();
    }
}
