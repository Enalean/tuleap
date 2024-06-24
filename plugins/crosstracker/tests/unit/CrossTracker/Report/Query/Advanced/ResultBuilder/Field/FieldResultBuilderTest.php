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

use Codendi_HTMLPurifier;
use DateTime;
use ForgeConfig;
use PFUser;
use Tracker;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
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
        array $first_value,
        array $second_value,
    ): SelectedValuesCollection {
        $purifier = Codendi_HTMLPurifier::instance();
        $builder  = new FieldResultBuilder(
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
            new TextResultBuilder(
                RetrieveArtifactStub::withArtifacts(
                    ArtifactTestBuilder::anArtifact(12)->inTracker($this->first_tracker)->build(),
                    ArtifactTestBuilder::anArtifact(15)->inTracker($this->second_tracker)->build(),
                ),
                new TextValueInterpreter(
                    $purifier,
                    CommonMarkInterpreter::build(
                        $purifier,
                    ),
                ),
            ),
            new NumericResultBuilder(),
        );

        return $builder->getResult(
            new Field(self::FIELD_NAME),
            $this->user,
            [$this->first_tracker, $this->second_tracker],
            [
                [
                    'id' => 12,
                    ...$first_value,
                ],
                [
                    'id' => 15,
                    ...$second_value,
                ],
            ],
        );
    }

    public function testItReturnsEmptyAsNothingHasBeenImplemented(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                ExternalFieldBuilder::anExternalField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build()
            ),
            [$this->field_hash => null],
            [$this->field_hash => null],
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
            [$this->field_hash => $first_date->getTimestamp()],
            [$this->field_hash => $second_date->getTimestamp()],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_DATE),
            $result->selected,
        );
        self::assertCount(2, $result->values);
    }

    public function testItReturnsValuesForTextField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                TextFieldBuilder::aTextField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                TextFieldBuilder::aTextField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            [$this->field_hash => '499P', "format_$this->field_hash" => 'text'],
            [$this->field_hash => 'V-Series.R', "format_$this->field_hash" => 'commonmark'],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_TEXT),
            $result->selected,
        );
        self::assertCount(2, $result->values);
    }

    public function testItReturnsValuesForNumericField(): void
    {
        $result = $this->getSelectedResult(
            RetrieveUsedFieldsStub::withFields(
                IntFieldBuilder::anIntField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                FloatFieldBuilder::aFloatField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->build(),
            ),
            ["int_$this->field_hash" => 42, "float_$this->field_hash" => null],
            ["int_$this->field_hash" => null, "float_$this->field_hash" => 3.1415],
        );

        self::assertEquals(
            new CrossTrackerSelectedRepresentation(self::FIELD_NAME, CrossTrackerSelectedType::TYPE_NUMERIC),
            $result->selected,
        );
        self::assertCount(2, $result->values);
    }
}
