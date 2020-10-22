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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data;

use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\Nature\ProjectIncrementArtifactLinkType;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\CopiedValues;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFields;

final class ProjectIncrementFieldsData
{
    /**
     * @var int
     * @psalm-readonly
     */
    private $artifact_link_field_id;
    /**
     * @var int
     * @psalm-readonly
     */
    private $source_artifact_id;
    /**
     * @var int
     * @psalm-readonly
     */
    private $title_field_id;
    /**
     * @var \Tracker_Artifact_ChangesetValue_String
     * @psalm-readonly
     */
    private $title_changeset_value;
    /**
     * @var int
     * @psalm-readonly
     */
    private $description_field_id;
    /**
     * @var \Tracker_Artifact_ChangesetValue_Text
     * @psalm-readonly
     */
    private $description_changeset_value;
    /**
     * @var int
     * @psalm-readonly
     */
    private $status_field_id;
    /**
     * @var MappedStatusValue
     * @psalm-readonly
     */
    private $mapped_status_value;
    /**
     * @var \Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_value;
    /**
     * @var \Tracker_Artifact_ChangesetValue
     */
    private $end_period_value;
    /**
     * @var int
     */
    private $start_date_field_id;
    /**
     * @var int
     */
    private $end_period_field_id;

    /**
     * @param \Tracker_Artifact_ChangesetValue_Date | \Tracker_Artifact_ChangesetValue_Numeric $end_period_value
     */
    private function __construct(
        int $artifact_link_field_id,
        int $source_artifact_id,
        int $title_field_id,
        \Tracker_Artifact_ChangesetValue_String $title_changeset_value,
        int $description_field_id,
        \Tracker_Artifact_ChangesetValue_Text $description_changeset_value,
        int $status_field_id,
        MappedStatusValue $mapped_status_value,
        int $start_date_field_id,
        \Tracker_Artifact_ChangesetValue_Date $start_date_value,
        int $end_period_field_id,
        \Tracker_Artifact_ChangesetValue $end_period_value
    ) {
        $this->artifact_link_field_id      = $artifact_link_field_id;
        $this->source_artifact_id          = $source_artifact_id;
        $this->title_field_id              = $title_field_id;
        $this->title_changeset_value       = $title_changeset_value;
        $this->description_field_id        = $description_field_id;
        $this->description_changeset_value = $description_changeset_value;
        $this->status_field_id             = $status_field_id;
        $this->mapped_status_value         = $mapped_status_value;
        $this->start_date_field_id         = $start_date_field_id;
        $this->start_date_value            = $start_date_value;
        $this->end_period_field_id         = $end_period_field_id;
        $this->end_period_value            = $end_period_value;
    }

    public static function fromCopiedValuesAndSynchronizedFields(
        CopiedValues $copied_values,
        MappedStatusValue $mapped_status_value,
        SynchronizedFields $target_fields
    ): self {
        return new self(
            (int) $target_fields->getArtifactLinkField()->getId(),
            $copied_values->getSourceArtifactId(),
            (int) $target_fields->getTitleField()->getId(),
            $copied_values->getTitleValue(),
            (int) $target_fields->getDescriptionField()->getId(),
            $copied_values->getDescriptionValue(),
            (int) $target_fields->getStatusField()->getId(),
            $mapped_status_value,
            (int) $target_fields->getTimeframeFields()->getStartDateField()->getId(),
            $copied_values->getStartDateValue(),
            (int) $target_fields->getTimeframeFields()->getEndPeriodField()->getId(),
            $copied_values->getEndPeriodValue()
        );
    }

    /**
     * @return array<int,string|array>
     */
    public function toFieldsDataArray(): array
    {
        return [
            $this->artifact_link_field_id => $this->toArtifactLinkFieldData(),
            $this->title_field_id         => $this->title_changeset_value->getValue(),
            $this->description_field_id   => $this->toTextFieldData($this->description_changeset_value),
            $this->status_field_id        => $this->mapped_status_value->getValues(),
            $this->start_date_field_id        => $this->start_date_value->getValue(),
            $this->end_period_field_id        => $this->end_period_value->getValue(),
        ];
    }

    /**
     * @return array{new_values: string, natures: array<string, string>}
     */
    private function toArtifactLinkFieldData(): array
    {
        return [
            'new_values' => (string) $this->source_artifact_id,
            'natures'    => [(string) $this->source_artifact_id => ProjectIncrementArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
    }

    /**
     * @return array{content: string, format: string}
     */
    private function toTextFieldData(\Tracker_Artifact_ChangesetValue_Text $changeset_value_text): array
    {
        return [
            'content' => $changeset_value_text->getValue(),
            'format'  => $changeset_value_text->getFormat()
        ];
    }
}
