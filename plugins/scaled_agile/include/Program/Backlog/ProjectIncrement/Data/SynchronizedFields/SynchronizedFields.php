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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields;

final class SynchronizedFields
{
    /**
     * @var \Tracker_FormElement_Field_ArtifactLink
     * @psalm-readonly
     */
    private $artifact_link_field;
    /**
     * @var \Tracker_FormElement_Field_Text
     * @psalm-readonly
     */
    private $title_field;
    /**
     * @var \Tracker_FormElement_Field_Text
     * @psalm-readonly
     */
    private $description_field;
    /**
     * @var \Tracker_FormElement_Field_List
     * @psalm-readonly
     */
    private $status_field;
    /**
     * @var TimeframeFields
     * @psalm-readonly
     */
    private $timeframe_fields;

    public function __construct(
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field,
        \Tracker_FormElement_Field_Text $title_field,
        \Tracker_FormElement_Field_Text $description_field,
        \Tracker_FormElement_Field_List $status_field,
        TimeframeFields $timeframe_fields
    ) {
        $this->artifact_link_field = $artifact_link_field;
        $this->title_field         = $title_field;
        $this->description_field   = $description_field;
        $this->status_field        = $status_field;
        $this->timeframe_fields    = $timeframe_fields;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifactLinkField(): \Tracker_FormElement_Field_ArtifactLink
    {
        return $this->artifact_link_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTitleField(): \Tracker_FormElement_Field_Text
    {
        return $this->title_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getDescriptionField(): \Tracker_FormElement_Field_Text
    {
        return $this->description_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatusField(): \Tracker_FormElement_Field_List
    {
        return $this->status_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTimeframeFields(): TimeframeFields
    {
        return $this->timeframe_fields;
    }

    /**
     * @return \Tracker_FormElement_Field[]
     * @psalm-mutation-free
     */
    public function toArrayOfFields(): array
    {
        return [
            $this->artifact_link_field,
            $this->title_field,
            $this->description_field,
            $this->status_field,
            $this->timeframe_fields->getStartDateField(),
            $this->timeframe_fields->getEndPeriodField()
        ];
    }
}
