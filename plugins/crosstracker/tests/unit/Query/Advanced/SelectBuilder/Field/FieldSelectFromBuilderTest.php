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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field;

use ParagonIE\EasyDB\EasyDB;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldSelectFromBuilderTest extends TestCase
{
    private const FIELD_NAME      = 'my_field';
    private const FIRST_FIELD_ID  = 134;
    private const SECOND_FIELD_ID = 334;
    private PFUser $user;
    private Tracker $first_tracker;
    private Tracker $second_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->user           = UserTestBuilder::buildWithId(133);
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->build();
    }

    private function getSelectFrom(
        RetrieveUsedFieldsStub $fields_retriever,
    ): IProvideParametrizedSelectAndFromSQLFragments {
        $tuleap_db = $this->createStub(EasyDB::class);
        $tuleap_db->method('escapeIdentifier');
        $builder = new FieldSelectFromBuilder(
            $fields_retriever,
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn(
                [self::FIRST_FIELD_ID, self::SECOND_FIELD_ID],
                FieldPermissionType::PERMISSION_READ,
            ),
            new DateSelectFromBuilder($tuleap_db),
            new TextSelectFromBuilder($tuleap_db),
            new NumericSelectFromBuilder($tuleap_db),
            new StaticListSelectFromBuilder($tuleap_db),
            new UGroupListSelectFromBuilder($tuleap_db),
            new UserListSelectFromBuilder($tuleap_db)
        );

        return $builder->getSelectFrom(
            new Field(self::FIELD_NAME),
            $this->user,
            [$this->first_tracker, $this->second_tracker],
        );
    }

    public function testItReturnsEmptyAsNothingHasBeenImplemented(): void
    {
        $result = $this->getSelectFrom(
            RetrieveUsedFieldsStub::withFields(
                ArtifactLinkFieldBuilder::anArtifactLinkField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->build(),
                FileFieldBuilder::aFileField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            ),
        );
        self::assertEmpty($result->getFrom());
        self::assertEmpty($result->getSelect());
        self::assertEmpty($result->getFromParameters());
    }

    public function testItReturnsSQLForDateField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(self::FIRST_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->withTime()
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            DateFieldBuilder::aDateField(self::SECOND_FIELD_ID)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsSQLForTextField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            TextFieldBuilder::aTextField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build(),
            TextFieldBuilder::aTextField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsSQLForNumericField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build(),
            FloatFieldBuilder::aFloatField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsSQLForListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsSQLForUserGroupListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListUserGroupBindBuilder::aUserGroupBind(
                ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserGroupBindBuilder::aUserGroupBind(
                ListFieldBuilder::aListField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsSQLForUserListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertNotEmpty($select_from->getSelect());
        self::assertNotEmpty($select_from->getFrom());
        self::assertNotEmpty($select_from->getFromParameters());
    }

    public function testItReturnsAnEmptySQLStateForFieldWhoIsNotKnownByAnyTracker(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(self::FIRST_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserBindBuilder::aUserBind(
                ListFieldBuilder::aListField(self::SECOND_FIELD_ID)
                    ->withName(self::FIELD_NAME)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $select_from = $this->getSelectFrom($fields_retriever);
        self::assertEmpty($select_from->getSelect());
        self::assertEmpty($select_from->getFrom());
        self::assertEmpty($select_from->getFromParameters());
    }
}
