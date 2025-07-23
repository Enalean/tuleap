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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class GatherSynchronizedFieldsStub implements GatherSynchronizedFields
{
    private function __construct(
        private RetrieveTitleFieldStub $title_stub,
        private RetrieveDescriptionFieldStub $description_stub,
        private RetrieveStatusFieldStub $status_stub,
        private RetrieveStartDateFieldStub $start_date_stub,
        private RetrieveEndPeriodFieldStub $end_period_stub,
        private RetrieveArtifactLinkFieldStub $artifact_link_stub,
    ) {
    }

    public static function withDefaults(): self
    {
        return self::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(370, 921, 381, 163, 631, 102),
        );
    }

    public static function withFieldsPreparations(SynchronizedFieldsStubPreparation ...$preparations): self
    {
        $titles         = [];
        $descriptions   = [];
        $statuses       = [];
        $start_dates    = [];
        $end_dates      = [];
        $durations      = [];
        $artifact_links = [];
        foreach ($preparations as $preparation) {
            if ($preparation->title) {
                $titles[] = $preparation->title;
            }
            if ($preparation->description) {
                $descriptions[] = $preparation->description;
            }
            if ($preparation->status) {
                $statuses[] = $preparation->status;
            }
            if ($preparation->start_date) {
                $start_dates[] = $preparation->start_date;
            }
            if ($preparation->end_date) {
                $end_dates[] = $preparation->end_date;
            }
            if ($preparation->duration) {
                $durations[] = $preparation->duration;
            }
            if ($preparation->artifact_link) {
                $artifact_links[] = $preparation->artifact_link;
            }
        }
        $duration_stub      = $durations !== []
            ? RetrieveEndPeriodFieldStub::withDurationFields(...$durations)
            : RetrieveEndPeriodFieldStub::withError();
        $end_period_stub    = $end_dates !== []
            ? RetrieveEndPeriodFieldStub::withEndDateFields(...$end_dates)
            : $duration_stub;
        $artifact_link_stub = $artifact_links !== []
            ? RetrieveArtifactLinkFieldStub::withFields(...$artifact_links)
            : RetrieveArtifactLinkFieldStub::withError();
        return new self(
            RetrieveTitleFieldStub::withFields(...$titles),
            RetrieveDescriptionFieldStub::withFields(...$descriptions),
            RetrieveStatusFieldStub::withFields(...$statuses),
            RetrieveStartDateFieldStub::withFields(...$start_dates),
            $end_period_stub,
            $artifact_link_stub,
        );
    }

    public static function withError(): self
    {
        return new self(
            RetrieveTitleFieldStub::withError(),
            RetrieveDescriptionFieldStub::withFields(DescriptionFieldReferenceStub::withDefaults()),
            RetrieveStatusFieldStub::withFields(StatusFieldReferenceStub::withDefaults()),
            RetrieveStartDateFieldStub::withFields(StartDateFieldReferenceStub::withDefaults()),
            RetrieveEndPeriodFieldStub::withEndDateFields(EndDateFieldReferenceStub::withDefaults()),
            RetrieveArtifactLinkFieldStub::withFields(ArtifactLinkFieldReferenceStub::withDefaults())
        );
    }

    #[\Override]
    public function getTitleField(TrackerIdentifier $program_increment, ?ConfigurationErrorsCollector $errors_collector): TitleFieldReference
    {
        return $this->title_stub->getTitleField($program_increment, $errors_collector);
    }

    #[\Override]
    public function getDescriptionField(TrackerIdentifier $program_increment): DescriptionFieldReference
    {
        return $this->description_stub->getDescriptionField($program_increment);
    }

    #[\Override]
    public function getStatusField(TrackerIdentifier $program_increment): StatusFieldReference
    {
        return $this->status_stub->getStatusField($program_increment);
    }

    #[\Override]
    public function getStartDateField(TrackerIdentifier $program_increment): StartDateFieldReference
    {
        return $this->start_date_stub->getStartDateField($program_increment);
    }

    #[\Override]
    public function getEndPeriodField(TrackerIdentifier $tracker_identifier): EndDateFieldReference|DurationFieldReference
    {
        return $this->end_period_stub->getEndPeriodField($tracker_identifier);
    }

    #[\Override]
    public function getArtifactLinkField(
        TrackerIdentifier $program_increment,
        ?ConfigurationErrorsCollector $errors_collector,
    ): ArtifactLinkFieldReference {
        return $this->artifact_link_stub->getArtifactLinkField($program_increment, $errors_collector);
    }
}
