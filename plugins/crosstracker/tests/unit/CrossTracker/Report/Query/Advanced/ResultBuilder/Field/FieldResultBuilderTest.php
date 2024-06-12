<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field;

use ForgeConfig;
use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\RetrieveUserPermissionOnFieldsStub;

final class FieldResultBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private const FIELD_NAME      = 'my_field';
    private const FIRST_FIELD_ID  = 134;
    private const SECOND_FIELD_ID = 334;
    private string $field_hash;
    private PFUser $user;
    private Tracker $first_tracker;
    private Tracker $second_tracker;

    protected function setUp(): void
    {
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
        $this->field_hash     = md5('my_field');
        $this->user           = UserTestBuilder::buildWithId(133);
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->build();
    }

    private function getSelectedResult(
        RetrieveUsedFieldsStub $fields_retriever,
    ): SelectedValuesCollection {
        $builder = new FieldResultBuilder(
            $fields_retriever,
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn(
                [self::FIRST_FIELD_ID, self::SECOND_FIELD_ID],
                FieldPermissionType::PERMISSION_READ,
            ),
        );

        return $builder->getResult(
            new Field(self::FIELD_NAME),
            $this->user,
            [$this->first_tracker, $this->second_tracker],
            [
                [
                    'id'              => 12,
                    $this->field_hash => 3,
                ],
                [
                    'id'              => 15,
                    $this->field_hash => 25,
                ],
            ],
        );
    }

    public function testItReturnsEmptyAsNothingHasBeenImplemented(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                IntFieldBuilder::anIntField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                FloatFieldBuilder::aFloatField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build()
            ),
        );

        self::assertNull($result->selected);
        self::assertEmpty($result->values);
    }
}
