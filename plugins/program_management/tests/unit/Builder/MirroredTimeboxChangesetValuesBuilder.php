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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;

final class MirroredTimeboxChangesetValuesBuilder
{
    public static function buildWithIdsAndValues(
        int $title_field_id,
        string $title_value,
        int $description_field_id,
        string $description_value,
        string $description_format,
        int $status_field_id,
        int $mapped_status_bind_value_id,
        int $start_date_field_id,
        int $start_date_value,
        int $end_date_field_id,
        int $end_date_value,
        int $artifact_link_field_id,
        ArtifactLinkValue $artifact_link_value,
    ): MirroredTimeboxChangesetValues {
        $status_mapper = MapStatusByValueStub::withSuccessiveBindValueIds($mapped_status_bind_value_id);
        $source_values = SourceTimeboxChangesetValuesBuilder::buildWithValues(
            $title_value,
            $description_value,
            $description_format,
            ['avadhuta'],
            $start_date_value,
            $end_date_value,
            $artifact_link_value->linked_artifact->getId(),
            1354021686
        );
        $target_fields = SynchronizedFieldReferencesBuilder::buildWithPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(
                $title_field_id,
                $description_field_id,
                $status_field_id,
                $start_date_field_id,
                $end_date_field_id,
                $artifact_link_field_id
            )
        );
        return MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $source_values,
            $target_fields,
            $artifact_link_value
        );
    }

    public static function buildWithDuration(
        int $duration_field_id,
        int $duration_value,
    ): MirroredTimeboxChangesetValues {
        $status_mapper = MapStatusByValueStub::withSuccessiveBindValueIds(8401);
        $source_values = SourceTimeboxChangesetValuesBuilder::buildWithDuration(
            'furacious',
            'encephalin lindackerite',
            'text',
            ['philosophization'],
            1455568188,
            $duration_value,
            46,
            1593932709
        );
        $target_fields = SynchronizedFieldReferencesBuilder::buildWithPreparations(
            SynchronizedFieldsStubPreparation::withDuration(
                1882,
                1652,
                5095,
                3928,
                $duration_field_id,
                8117
            )
        );
        return MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
            $status_mapper,
            $source_values,
            $target_fields,
            null
        );
    }
}
