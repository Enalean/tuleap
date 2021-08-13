<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ProgramIncrementFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\SourceChangesetValuesCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldsBuilder;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;

final class ProgramIncrementFieldsDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const MAPPED_STATUS_BIND_VALUE_ID = 3001;

    public function testItReturnsFieldsDataAsArrayForArtifactCreator(): void
    {
        $copied_values       = SourceChangesetValuesCollectionBuilder::buildWithValues(
            'Program Release',
            '<p>Description</p>',
            'html',
            [2001],
            '2020-10-01',
            '2020-10-10',
            112
        );
        $target_fields       = SynchronizedFieldsBuilder::buildWithIds(1001, 1002, 1003, 1004, 1005, 1006);
        $mapped_status_value = MappedStatusValue::fromStatusValueAndListField(
            MapStatusByValueStub::withValues(self::MAPPED_STATUS_BIND_VALUE_ID),
            $copied_values->getStatusValue(),
            $target_fields->getStatusField()
        );

        $fields_data = ProgramIncrementFields::fromSourceChangesetValuesAndSynchronizedFields(
            $copied_values,
            $mapped_status_value,
            $target_fields
        );

        self::assertEquals(
            [
                1001 => ['new_values' => '112', 'natures' => ['112' => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME]],
                1002 => 'Program Release',
                1003 => ['content' => '<p>Description</p>', 'format' => 'html'],
                1004 => [self::MAPPED_STATUS_BIND_VALUE_ID],
                1005 => '2020-10-01',
                1006 => '2020-10-10'
            ],
            $fields_data->toFieldsDataArray()
        );
    }
}
