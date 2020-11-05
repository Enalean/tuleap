<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\TimeFrameFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\MissingTimeFrameFieldException;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class TimeFrameFieldsAdapter
{
    /**
     * @var SemanticTimeframeBuilder
     */
    private $timeframe_builder;

    public function __construct(
        SemanticTimeframeBuilder $timeframe_builder
    ) {
        $this->timeframe_builder    = $timeframe_builder;
    }

    /**
     * @throws MissingTimeFrameFieldException
     */
    public function build(TrackerData $replication_tracker_data): TimeFrameFields
    {
        $source_tracker   = $replication_tracker_data->getFullTracker();
        $semantic         = $this->timeframe_builder->getSemantic($source_tracker);
        $start_date_field = $semantic->getStartDateField();
        if (! $start_date_field) {
            throw new MissingTimeFrameFieldException($replication_tracker_data->getTrackerId(), 'start date');
        }

        $start_date_field_data = new FieldData($start_date_field);

        $duration_field = $semantic->getDurationField();
        if ($duration_field !== null) {
            $duration_field_data = new FieldData($duration_field);
            return TimeFrameFields::fromStartDateAndDuration($start_date_field_data, $duration_field_data);
        }
        $end_date_field = $semantic->getEndDateField();
        if ($end_date_field !== null) {
            $end_date_field_data = new FieldData($end_date_field);
            return TimeFrameFields::fromStartAndEndDates($start_date_field_data, $end_date_field_data);
        }
        throw new MissingTimeFrameFieldException($replication_tracker_data->getTrackerId(), 'end date or duration');
    }
}
