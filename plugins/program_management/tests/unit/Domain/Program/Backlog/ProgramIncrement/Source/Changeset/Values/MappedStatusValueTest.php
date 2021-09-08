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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReferenceProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;

final class MappedStatusValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_BIND_VALUE_ID  = 3786;
    private const SECOND_BIND_VALUE_ID = 1844;

    public function testItBuildsFromStatusValueAndListField(): void
    {
        $status_field  = $this->buildStatusField();
        $status_value  = StatusValue::fromSynchronizedFields(
            RetrieveStatusValuesStub::withValues('Planned', 'Current'),
            SynchronizedFieldReferences::fromTrackerIdentifier(GatherSynchronizedFieldsStub::withDefaults(), TrackerIdentifierStub::buildWithDefault())
        );
        $mapped_status = MappedStatusValue::fromStatusValueAndListField(
            MapStatusByValueStub::withValues(self::FIRST_BIND_VALUE_ID, self::SECOND_BIND_VALUE_ID),
            $status_value,
            $status_field
        );
        $mapped_values = $mapped_status->getValues();
        self::assertContains(self::FIRST_BIND_VALUE_ID, $mapped_values);
        self::assertContains(self::SECOND_BIND_VALUE_ID, $mapped_values);
    }

    private function buildStatusField(): StatusFieldReferenceProxy
    {
        return StatusFieldReferenceProxy::fromTrackerField(new \Tracker_FormElement_Field_Selectbox(
            344,
            29,
            340,
            'status',
            'Status',
            'Irrelevant',
            true,
            'P',
            false,
            '',
            1
        ));
    }
}
