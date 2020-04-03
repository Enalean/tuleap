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
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;
use Tuleap\Tracker\Semantic\Timeframe\Events\DoesAPluginRenderAChartBasedOnSemanticTimeframeForTrackerEvent;

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
        ?Tracker_FormElement_Field_Date $start_date_field,
        ?Tracker_FormElement_Field_Numeric $duration_field,
        ?Tracker_FormElement_Field_Date $end_date_field
    ): SemanticTimeframeAdministrationPresenter {
        $usable_start_date_fields = $this->buildSelectBoxEntries(
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['date']),
            $start_date_field
        );

        $usable_end_date_fields = $this->buildSelectBoxEntries(
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['date']),
            $end_date_field
        );

        $usable_numeric_fields = $this->buildSelectBoxEntries(
            $this->tracker_formelement_factory->getUsedFormElementsByType($tracker, ['int', 'float', 'computed']),
            $duration_field
        );

        $has_tracker_charts = $this->doesTrackerHaveCharts($tracker);
        $is_semantic_in_start_date_duration_mode = $duration_field !== null;

        return new SemanticTimeframeAdministrationPresenter(
            $csrf,
            $tracker,
            $target_url,
            $has_tracker_charts,
            $is_semantic_in_start_date_duration_mode,
            $usable_start_date_fields,
            $usable_end_date_fields,
            $usable_numeric_fields,
            $start_date_field,
            $duration_field,
            $end_date_field
        );
    }

    private function buildSelectBoxEntries(array $fields, ?\Tracker_FormElement_Field $current_field): array
    {
        return array_map(function (\Tracker_FormElement_Field $field) use ($current_field) {
            return [
                'id'          => $field->getId(),
                'label'       => $field->getLabel(),
                'is_selected' => $current_field && (int) $field->getId() === (int) $current_field->getId()
            ];
        }, $fields);
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
