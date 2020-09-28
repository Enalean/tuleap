<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Tuleap\MultiProjectBacklog\Aggregator\MirroredArtifactLink\MirroredMilestoneArtifactLinkType;

final class MirrorMilestoneFieldsData
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
    private $aggregator_milestone_id;
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

    private function __construct(
        int $artifact_link_field_id,
        int $aggregator_milestone_id,
        int $title_field_id,
        \Tracker_Artifact_ChangesetValue_String $title_changeset_value
    ) {
        $this->artifact_link_field_id  = $artifact_link_field_id;
        $this->aggregator_milestone_id = $aggregator_milestone_id;
        $this->title_field_id          = $title_field_id;
        $this->title_changeset_value   = $title_changeset_value;
    }

    public static function fromCopiedValuesAndTargetFields(
        CopiedValues $copied_values,
        TargetFields $target_fields
    ): self {
        return new self(
            (int) $target_fields->getArtifactLinkField()->getId(),
            $copied_values->getArtifactId(),
            (int) $target_fields->getTitleField()->getId(),
            $copied_values->getTitleValue()
        );
    }

    /**
     * @return array<int,string|array>
     */
    public function toFieldsDataArray(): array
    {
        return [
            $this->artifact_link_field_id => $this->toArtifactLinkFieldData(),
            $this->title_field_id         => $this->title_changeset_value->getValue()
        ];
    }

    /**
     * @return array{new_values: string, natures: array<string, string>}
     */
    private function toArtifactLinkFieldData(): array
    {
        return [
            'new_values' => (string) $this->aggregator_milestone_id,
            'natures'    => [(string) $this->aggregator_milestone_id => MirroredMilestoneArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
    }
}
