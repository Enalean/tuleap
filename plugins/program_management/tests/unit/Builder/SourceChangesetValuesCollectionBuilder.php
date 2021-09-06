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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Tests\Stub\BuildSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;

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
        return SourceChangesetValuesCollection::fromReplication(
            BuildSynchronizedFieldsStub::withDefault(),
            RetrieveFieldValuesGathererStub::withGatherer(
                GatherFieldValuesStub::withValues(
                    $title,
                    $description_content,
                    $description_format,
                    $start_date,
                    $end_date,
                    $status
                )
            ),
            ReplicationDataBuilder::buildWithArtifactId($source_program_increment_id)
        );
    }
}
