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

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\TrackerData;

class SynchronizedFieldsAdapter
{
    /**
     * @var ArtifactLinkFieldAdapter
     */
    private $artifact_link_adapter;
    /**
     * @var TitleFieldAdapter
     */
    private $field_title_adapter;
    /**
     * @var DescriptionFieldAdapter
     */
    private $field_description_adapter;
    /**
     * @var StatusFieldAdapter
     */
    private $field_status_adapter;
    /**
     * @var TimeFrameFieldsAdapter
     */
    private $fields_time_frame_adapter;

    public function __construct(
        ArtifactLinkFieldAdapter $artifact_link_adapter,
        TitleFieldAdapter $field_title_adapter,
        DescriptionFieldAdapter $field_description_adapter,
        StatusFieldAdapter $field_status_adapter,
        TimeFrameFieldsAdapter $fields_time_frame_adapter
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
    public function build(TrackerData $source_tracker): SynchronizedFields
    {
        $timeframe_fields = $this->fields_time_frame_adapter->build($source_tracker);
        return new SynchronizedFields(
            $this->artifact_link_adapter->build($source_tracker),
            $this->field_title_adapter->build($source_tracker),
            $this->field_description_adapter->build($source_tracker),
            $this->field_status_adapter->build($source_tracker),
            $timeframe_fields->getStartDateField(),
            $timeframe_fields->getEndPeriodField()
        );
    }
}
