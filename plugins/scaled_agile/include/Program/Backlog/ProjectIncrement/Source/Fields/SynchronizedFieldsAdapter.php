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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

use Tuleap\ScaledAgile\TrackerData;

class SynchronizedFieldsAdapter
{
    /**
     * @var FieldArtifactLinkAdapter
     */
    private $artifact_link_adapter;
    /**
     * @var FieldTitleAdapter
     */
    private $field_title_adapter;
    /**
     * @var FieldDescriptionAdapter
     */
    private $field_description_adapter;
    /**
     * @var FieldStatusAdapter
     */
    private $field_status_adapter;
    /**
     * @var FieldsTimeFrameAdapter
     */
    private $fields_time_frame_adapter;

    public function __construct(
        FieldArtifactLinkAdapter $artifact_link_adapter,
        FieldTitleAdapter $field_title_adapter,
        FieldDescriptionAdapter $field_description_adapter,
        FieldStatusAdapter $field_status_adapter,
        FieldsTimeFrameAdapter $fields_time_frame_adapter
    ) {
        $this->artifact_link_adapter     = $artifact_link_adapter;
        $this->field_title_adapter       = $field_title_adapter;
        $this->field_description_adapter = $field_description_adapter;
        $this->field_status_adapter      = $field_status_adapter;
        $this->fields_time_frame_adapter = $fields_time_frame_adapter;
    }

    /**
     * @throws FieldSynchronizationException
     */
    public function build(TrackerData $source_tracker): SynchronizedFieldsData
    {
        $timeframe_fields = $this->fields_time_frame_adapter->build($source_tracker);
        return new SynchronizedFieldsData(
            $this->artifact_link_adapter->build($source_tracker),
            $this->field_title_adapter->build($source_tracker),
            $this->field_description_adapter->build($source_tracker),
            $this->field_status_adapter->build($source_tracker),
            $timeframe_fields->getStartDateField(),
            $timeframe_fields->getEndPeriodField()
        );
    }
}
