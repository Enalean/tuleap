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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

final class GatherSynchronizedFieldsStub implements GatherSynchronizedFields
{
    private function __construct(
        private RetrieveTitleFieldStub $title_stub,
        private RetrieveDescriptionFieldStub $description_stub,
        private RetrieveStatusFieldStub $status_stub,
        private RetrieveStartDateFieldStub $start_date_stub,
        private RetrieveEndPeriodFieldStub $end_period_stub,
        private RetrieveArtifactLinkFieldStub $artifact_link_stub
    ) {
    }

    public static function withDefaults(): self
    {
        return self::withFieldIds(370, 921, 381, 163, 631, 102);
    }

    public static function withFieldIds(
        int $title_field_id,
        int $description_field_id,
        int $status_field_id,
        int $start_date_field_id,
        int $end_period_field_id,
        int $artifact_link_field_id
    ): self {
        return new self(
            RetrieveTitleFieldStub::withField(TitleFieldReferenceStub::withId($title_field_id)),
            RetrieveDescriptionFieldStub::withField(DescriptionFieldReferenceStub::withId($description_field_id)),
            RetrieveStatusFieldStub::withField(StatusFieldReferenceStub::withId($status_field_id)),
            RetrieveStartDateFieldStub::withField(StartDateFieldReferenceStub::withId($start_date_field_id)),
            RetrieveEndPeriodFieldStub::withField(EndPeriodFieldReferenceStub::withId($end_period_field_id)),
            RetrieveArtifactLinkFieldStub::withField(ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id))
        );
    }

    public static function withError(): self
    {
        return new self(
            RetrieveTitleFieldStub::withError(),
            RetrieveDescriptionFieldStub::withField(DescriptionFieldReferenceStub::withDefaults()),
            RetrieveStatusFieldStub::withField(StatusFieldReferenceStub::withDefaults()),
            RetrieveStartDateFieldStub::withField(StartDateFieldReferenceStub::withDefaults()),
            RetrieveEndPeriodFieldStub::withField(EndPeriodFieldReferenceStub::withDefaults()),
            RetrieveArtifactLinkFieldStub::withField(ArtifactLinkFieldReferenceStub::withDefaults())
        );
    }

    public function getTitleField(TrackerIdentifier $program_increment): TitleFieldReference
    {
        return $this->title_stub->getTitleField($program_increment);
    }

    public function getDescriptionField(TrackerIdentifier $program_increment): DescriptionFieldReference
    {
        return $this->description_stub->getDescriptionField($program_increment);
    }

    public function getStatusField(TrackerIdentifier $program_increment): StatusFieldReference
    {
        return $this->status_stub->getStatusField($program_increment);
    }

    public function getStartDateField(TrackerIdentifier $program_increment): StartDateFieldReference
    {
        return $this->start_date_stub->getStartDateField($program_increment);
    }

    public function getEndPeriodField(TrackerIdentifier $program_increment): EndPeriodFieldReference
    {
        return $this->end_period_stub->getEndPeriodField($program_increment);
    }

    public function getArtifactLinkField(
        TrackerIdentifier $program_increment
    ): ArtifactLinkFieldReference {
        return $this->artifact_link_stub->getArtifactLinkField($program_increment);
    }
}
