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

use DateTime;
use ForgeConfig;
use PFUser;
use Tracker;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
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
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
        $this->field_hash     = md5('my_field');
        $this->user           = UserTestBuilder::buildWithId(133);
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->build();
    }

    private function getSelectedResult(
        RetrieveUsedFieldsStub $fields_retriever,
        mixed $first_value,
        mixed $second_value,
    ): SelectedValuesCollection {
        $builder = new FieldResultBuilder(
            $fields_retriever,
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn(
                [self::FIRST_FIELD_ID, self::SECOND_FIELD_ID],
                FieldPermissionType::PERMISSION_READ,
            ),
            new DateResultBuilder(
                RetrieveArtifactStub::withArtifacts(
                    ArtifactTestBuilder::anArtifact(12)->inTracker($this->first_tracker)->build(),
                    ArtifactTestBuilder::anArtifact(15)->inTracker($this->second_tracker)->build(),
                ),
                $fields_retriever,
            ),
        );

        return $builder->getResult(
            new Field(self::FIELD_NAME),
            $this->user,
            [$this->first_tracker, $this->second_tracker],
            [
                [
                    'id'              => 12,
                    $this->field_hash => $first_value,
                ],
                [
                    'id'              => 15,
                    $this->field_hash => $second_value,
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
            12,
            25,
        );

        self::assertNull($result->selected);
        self::assertEmpty($result->values);
    }

    public function testItReturnsValuesForDateField(): void
    {
        $first_date  = new DateTime('2024-06-12 11:30');
        $second_date = new DateTime('2024-06-12 00:00');
        $result      = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                DateFieldBuilder::aDateField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->withTime()
                    ->inTracker($this->first_tracker)
                    ->build(),
                DateFieldBuilder::aDateField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            $first_date->getTimestamp(),
            $second_date->getTimestamp(),
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_DATE),
            $result->selected,
        );
        self::assertCount(2, $result->values);
    }
}
