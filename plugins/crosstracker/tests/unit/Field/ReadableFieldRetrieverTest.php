<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Field;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReadableFieldRetrieverTest extends TestCase
{
    public const FIELD_NAME = 'La Voiture Noire';

    public const FIELD_1_ID = 628;

    public const FIELD_2_ID = 630;

    public const FIELD_3_ID = 250;


    public function testItRetrievesOnlyTheFieldsUserCanRead(): void
    {
        $user      = UserTestBuilder::buildWithId(105);
        $tracker_1 = TrackerTestBuilder::aTracker()->withId(3)->build();

        $field_1_readable = IntFieldBuilder::anIntField(self::FIELD_1_ID)
                ->withName(self::FIELD_NAME)
                    ->inTracker($tracker_1)
                        ->build();

        $tracker_2            = TrackerTestBuilder::aTracker()->withId(4)->build();
        $field_2_not_readable =  IntFieldBuilder::anIntField(self::FIELD_2_ID)
            ->withName(self::FIELD_NAME)
            ->inTracker($tracker_2)
            ->build();

        $tracker_3        = TrackerTestBuilder::aTracker()->withId(5)->build();
        $field_3_readable =  IntFieldBuilder::anIntField(self::FIELD_3_ID)
            ->withName(self::FIELD_NAME)
            ->inTracker($tracker_3)
            ->build();

        $readable_field_retriever = new ReadableFieldRetriever(
            RetrieveUsedFieldsStub::withFields($field_1_readable, $field_2_not_readable, $field_3_readable),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn(
                [self::FIELD_1_ID, self::FIELD_3_ID],
                FieldPermissionType::PERMISSION_READ,
            )
        );
        $result                   = $readable_field_retriever->retrieveFieldsUserCanRead(
            new Field(self::FIELD_NAME),
            $user,
            [$tracker_1->getId(), $tracker_2->getId(), $tracker_3->getId()]
        );

        self::assertCount(2, $result);
        self::assertEqualsCanonicalizing([$field_1_readable, $field_3_readable], $result);
    }
}
