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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
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
        return self::withFieldsPreparations(
            new SynchronizedFieldsStubPreparation(370, 921, 381, 163, 631, 102),
        );
    }

    public static function withFieldsPreparations(SynchronizedFieldsStubPreparation ...$preparations): self
    {
        $titles         = [];
        $descriptions   = [];
        $statuses       = [];
        $start_dates    = [];
        $end_periods    = [];
        $artifact_links = [];
        foreach ($preparations as $preparation) {
            $titles[]         = $preparation->title;
            $descriptions[]   = $preparation->description;
            $statuses[]       = $preparation->status;
            $start_dates[]    = $preparation->start_date;
            $end_periods[]    = $preparation->end_period;
            $artifact_links[] = $preparation->artifact_link;
        }
        return new self(
            RetrieveTitleFieldStub::withFields(...$titles),
            RetrieveDescriptionFieldStub::withFields(...$descriptions),
            RetrieveStatusFieldStub::withFields(...$statuses),
            RetrieveStartDateFieldStub::withFields(...$start_dates),
            RetrieveEndPeriodFieldStub::withFields(...$end_periods),
            RetrieveArtifactLinkFieldStub::withFields(...$artifact_links)
        );
    }

    public static function withError(): self
    {
        return new self(
            RetrieveTitleFieldStub::withError(),
            RetrieveDescriptionFieldStub::withFields(DescriptionFieldReferenceStub::withDefaults()),
            RetrieveStatusFieldStub::withFields(StatusFieldReferenceStub::withDefaults()),
            RetrieveStartDateFieldStub::withFields(StartDateFieldReferenceStub::withDefaults()),
            RetrieveEndPeriodFieldStub::withFields(EndPeriodFieldReferenceStub::withDefaults()),
            RetrieveArtifactLinkFieldStub::withFields(ArtifactLinkFieldReferenceStub::withDefaults())
        );
    }

    public function getTitleField(TrackerIdentifier $program_increment, ?ConfigurationErrorsCollector $errors_collector): TitleFieldReference
    {
        return $this->title_stub->getTitleField($program_increment, $errors_collector);
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
