<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DurationValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;

/**
 * I format changeset values to the array format expected by Tracker plugin API
 */
final class ChangesetValuesFormatter
{
    public function __construct(
        private ArtifactLinkValueFormatter $artifact_link_formatter,
        private DescriptionValueFormatter $description_formatter,
        private DateValueFormatter $date_value_formatter,
    ) {
    }

    /**
     * @return array<int,string|int|array>
     */
    public function formatForTrackerPlugin(MirroredTimeboxChangesetValues $values): array
    {
        $formatted_end_period_value = $values->end_period_value instanceof DurationValue
            ? $values->end_period_value->getValue()
            : $this->date_value_formatter->formatForTrackerPlugin($values->end_period_value);

        return [
            $values->artifact_link_field->getId() => $this->artifact_link_formatter->formatForTrackerPlugin(
                $values->artifact_link_value
            ),
            $values->title_field->getId()         => $values->title_value->getValue(),
            $values->description_field->getId()   => $this->description_formatter->formatForTrackerPlugin(
                $values->description_value
            ),
            $values->status_field->getId()        => $values->mapped_status_value->getValues(),
            $values->start_date_field->getId()    => $this->date_value_formatter->formatForTrackerPlugin($values->start_date_value),
            $values->end_period_field->getId()    => $formatted_end_period_value,
        ];
    }

    /**
     * @return array<int,array>
     */
    public function formatArtifactLink(ArtifactLinkFieldReference $artifact_link_field, ArtifactLinkValue $value): array
    {
        return [
            $artifact_link_field->getId() => $this->artifact_link_formatter->formatForTrackerPlugin($value),
        ];
    }
}
