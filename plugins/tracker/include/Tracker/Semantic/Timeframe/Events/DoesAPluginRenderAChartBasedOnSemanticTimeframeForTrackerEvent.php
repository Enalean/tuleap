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

namespace Tuleap\Tracker\Semantic\Timeframe\Events;

class DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent
{
    public const NAME = "doesAPluginRenderAChartBasedOnSemanticTimeframeForTracker";

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var bool
     */
    private $does_a_plugin_render_a_chart_for_tracker = false;

    public function __construct(\Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTracker(): \Tracker
    {
        return $this->tracker;
    }

    public function setItRendersAChartForTracker(): void
    {
        $this->does_a_plugin_render_a_chart_for_tracker = true;
    }

    public function doesAPluginRenderAChartForTracker(): bool
    {
        return $this->does_a_plugin_render_a_chart_for_tracker;
    }
}
