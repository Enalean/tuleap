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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveDescriptionValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveEndPeriodValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStartDateValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueStub;

final class SourceChangesetValuesCollectionBuilder
{
    public static function build(): SourceChangesetValuesCollection
    {
        return self::buildWithValues(
            'Program Release',
            'Description',
            'text',
            ['Planned'],
            '2020-10-01',
            '2020-10-10',
            112
        );
    }

    public static function buildWithStatusValues(string ...$status_values): SourceChangesetValuesCollection
    {
        return self::buildWithValues(
            'Program Increment',
            '<p>Description</p>',
            'html',
            $status_values,
            '2021-08-01',
            '2021-08-31',
            123
        );
    }

    /**
     * @param string[] $status
     */
    public static function buildWithValues(
        string $title,
        string $description_content,
        string $description_format,
        array $status,
        string $start_date,
        string $end_date,
        int $source_program_increment_id
    ): SourceChangesetValuesCollection {
        $replication_data    = ReplicationDataBuilder::buildWithArtifactId($source_program_increment_id);
        $synchronized_fields = SynchronizedFieldsBuilder::build();

        $title_value         = TitleValue::fromReplicationDataAndSynchronizedFields(
            RetrieveTitleValueStub::withValue($title),
            $replication_data,
            $synchronized_fields
        );
        $description_value   = DescriptionValue::fromReplicationDataAndSynchronizedFields(
            RetrieveDescriptionValueStub::withValue($description_content, $description_format),
            $replication_data,
            $synchronized_fields
        );
        $status_values       = StatusValue::fromReplicationAndSynchronizedFields(
            RetrieveStatusValuesStub::withValues(...$status),
            $replication_data,
            $synchronized_fields
        );
        $start_date_value    = StartDateValue::fromReplicationAndSynchronizedFields(
            RetrieveStartDateValueStub::withValue($start_date),
            $replication_data,
            $synchronized_fields
        );
        $end_period_value    = EndPeriodValue::fromReplicationAndSynchronizedFields(
            RetrieveEndPeriodValueStub::withValue($end_date),
            $replication_data,
            $synchronized_fields
        );
        $artifact_link_value = ArtifactLinkValue::fromReplicationData($replication_data);
        $submission_date     = new SubmissionDate(1234567890);

        return new SourceChangesetValuesCollection(
            $source_program_increment_id,
            $title_value,
            $description_value,
            $status_values,
            $submission_date,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }
}
