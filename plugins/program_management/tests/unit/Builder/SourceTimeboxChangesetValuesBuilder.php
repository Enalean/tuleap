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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveChangesetSubmissionDateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;

final class SourceTimeboxChangesetValuesBuilder
{
    public static function build(): SourceTimeboxChangesetValues
    {
        return self::buildWithSourceTimeboxId(112);
    }

    public static function buildWithSourceTimeboxId(int $source_timebox_id): SourceTimeboxChangesetValues
    {
        return self::buildWithValues(
            'Program Release',
            'Description',
            'text',
            ['Planned'],
            '2020-10-01',
            '2020-10-10',
            $source_timebox_id,
            1234567890
        );
    }

    public static function buildWithSubmissionDate(int $submission_date): SourceTimeboxChangesetValues
    {
        return self::buildWithValues(
            'Program Release',
            'Description',
            'text',
            ['Planned'],
            '2020-10-01',
            '2020-10-10',
            112,
            $submission_date
        );
    }

    /**
     * @param string[] $status
     */
    public static function buildWithValuesAndSubmissionDate(
        string $title,
        string $description_content,
        string $description_format,
        array $status,
        string $start_date,
        string $end_date,
        int $submission_date
    ): SourceTimeboxChangesetValues {
        return self::buildWithValues(
            $title,
            $description_content,
            $description_format,
            $status,
            $start_date,
            $end_date,
            112,
            $submission_date
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
        int $source_timebox_id,
        int $submission_date
    ): SourceTimeboxChangesetValues {
        return SourceTimeboxChangesetValues::fromMirroringOrder(
            GatherSynchronizedFieldsStub::withDefaults(),
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
            RetrieveChangesetSubmissionDateStub::withDate($submission_date),
            ProgramIncrementCreationBuilder::buildWithProgramIncrementId($source_timebox_id)
        );
    }
}
