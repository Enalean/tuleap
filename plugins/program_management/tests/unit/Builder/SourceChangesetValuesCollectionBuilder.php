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
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStartDateValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueStub;

final class SourceChangesetValuesCollectionBuilder
{
    public static function build(): SourceChangesetValuesCollection
    {
        return self::buildWithValues(
            'Program Release',
            'Description',
            'text',
            [2000],
            '2020-10-01',
            '2020-10-10',
            112
        );
    }

    /**
     * @param \Tracker_FormElement_Field_List_Bind_StaticValue[] $status_values
     */
    public static function buildWithStatusValues(array $status_values): SourceChangesetValuesCollection
    {
        return self::buildWithListBind(
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
     * @param int[] $status_values
     */
    public static function buildWithValues(
        string $title_value,
        string $description_value,
        string $description_format,
        array $status_values,
        string $start_date_value,
        string $end_period_value,
        int $source_program_increment_id
    ): SourceChangesetValuesCollection {
        $list_values = [];
        foreach ($status_values as $bind_value_id) {
            $list_values[] = new \Tracker_FormElement_Field_List_Bind_StaticValue(
                $bind_value_id,
                'Irrelevant',
                'Irrelevant',
                0,
                0
            );
        }
        return self::buildWithListBind(
            $title_value,
            $description_value,
            $description_format,
            $list_values,
            $start_date_value,
            $end_period_value,
            $source_program_increment_id
        );
    }

    /**
     * @param \Tracker_FormElement_Field_List_Bind_StaticValue[] $list_values
     */
    private static function buildWithListBind(
        string $title_value,
        string $description_value,
        string $description_format,
        array $list_values,
        string $start_date_value,
        string $end_period_value,
        int $source_program_increment_id
    ): SourceChangesetValuesCollection {
        $replication_data    = ReplicationDataBuilder::buildWithArtifactId($source_program_increment_id);
        $synchronized_fields = SynchronizedFieldsBuilder::build();

        $title_value         = TitleValue::fromReplicationDataAndSynchronizedFields(
            RetrieveTitleValueStub::withValue($title_value),
            $replication_data,
            $synchronized_fields
        );
        $description_value   = DescriptionValue::fromReplicationDataAndSynchronizedFields(
            RetrieveDescriptionValueStub::withValue($description_value, $description_format),
            $replication_data,
            $synchronized_fields
        );
        $status_values       = new StatusValue($list_values);
        $start_date_value    = StartDateValue::fromReplicationAndSynchronizedFields(
            RetrieveStartDateValueStub::withValue($start_date_value),
            $replication_data,
            $synchronized_fields
        );
        $end_period_value    = new EndPeriodValue($end_period_value);
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
