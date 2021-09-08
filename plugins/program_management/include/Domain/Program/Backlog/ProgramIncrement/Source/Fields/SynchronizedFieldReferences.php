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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

/**
 * I hold all synchronized field references (identifier + label) for a given Timebox or Mirrored Timebox.
 * Synchronized fields are: Artifact link field, Title semantic field, Description semantic field,
 * Status semantic field, Timeframe semantic fields
 * @psalm-immutable
 */
final class SynchronizedFieldReferences
{
    private function __construct(
        public TitleFieldReference $title,
        public DescriptionFieldReference $description,
        public StatusFieldReference $status,
        public StartDateFieldReference $start_date,
        public EndPeriodFieldReference $end_period,
        public ArtifactLinkFieldReference $artifact_link
    ) {
    }

    /**
     * @throws NoArtifactLinkFieldException
     * @throws FieldRetrievalException
     * @throws MissingTimeFrameFieldException
     * @throws TitleFieldHasIncorrectTypeException
     */
    public static function fromTrackerIdentifier(
        GatherSynchronizedFields $gatherer,
        TrackerIdentifier $tracker_reference
    ): self {
        $title         = $gatherer->getTitleField($tracker_reference);
        $description   = $gatherer->getDescriptionField($tracker_reference);
        $status        = $gatherer->getStatusField($tracker_reference);
        $start_date    = $gatherer->getStartDateField($tracker_reference);
        $end_period    = $gatherer->getEndPeriodField($tracker_reference);
        $artifact_link = $gatherer->getArtifactLinkField($tracker_reference);
        return new self($title, $description, $status, $start_date, $end_period, $artifact_link);
    }

    /**
     * @return array<int, true>
     */
    public function getSynchronizedFieldIDsAsKeys(): array
    {
        return [
            $this->artifact_link->getId() => true,
            $this->title->getId()         => true,
            $this->description->getId()   => true,
            $this->status->getId()        => true,
            $this->start_date->getId()    => true,
            $this->end_period->getId()    => true,
        ];
    }

    public function getAllFields(): array
    {
        return [
            $this->artifact_link,
            $this->title,
            $this->description,
            $this->status,
            $this->start_date,
            $this->end_period,
        ];
    }
}
