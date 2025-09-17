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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field;

use BaseLanguageFactory;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ExternalFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldFromWhereBuilderTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const string FIELD_NAME = 'my_field';
    private \PFUser $user;
    private \Tuleap\Tracker\Tracker $first_tracker;
    private \Tuleap\Tracker\Tracker $second_tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->user           = UserTestBuilder::buildWithId(133);
        $this->first_tracker  = TrackerTestBuilder::aTracker()->withId(38)->build();
        $this->second_tracker = TrackerTestBuilder::aTracker()->withId(4)->build();
    }

    private function getFromWhere(
        RetrieveUsedFieldsStub $fields_retriever,
        ValueWrapper $value_wrapper,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $db = $this->createStub(EasyDB::class);
        $db->method('escapeLikeValue')->willReturnArgument(0);

        $date_time_value_rounder  = new DateTimeValueRounder();
        $field_from_where_builder = new ListFromWhereBuilder();
        $builder                  = new FieldFromWhereBuilder(
            $fields_retriever,
            new FieldTypeRetrieverWrapper(RetrieveFieldTypeStub::withDetectionOfType()),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Numeric\NumericFromWhereBuilder(),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Text\TextFromWhereBuilder($db),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Date\DateFromWhereBuilder($date_time_value_rounder),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\Datetime\DatetimeFromWhereBuilder($date_time_value_rounder),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\StaticList\StaticListFromWhereBuilder($field_from_where_builder),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\UGroupList\UGroupListFromWhereBuilder(
                new UgroupLabelConverter(new ListFieldBindValueNormalizer(), new BaseLanguageFactory()),
                $field_from_where_builder,
            ),
            new \Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Field\UserList\UserListFromWhereBuilder($field_from_where_builder),
        );
        $field                    = new Field(self::FIELD_NAME);
        return $builder->getFromWhere(
            $field,
            new EqualComparison($field, $value_wrapper),
            $this->user,
            [$this->first_tracker, $this->second_tracker]
        );
    }

    public function testItReturnsSQLForNumericField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            IntegerFieldBuilder::anIntField(134)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            IntegerFieldBuilder::anIntField(859)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper(5));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForTextField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            StringFieldBuilder::aStringField(209)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            StringFieldBuilder::aStringField(134)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('monocarpal'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForDateField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(125)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            DateFieldBuilder::aDateField(334)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('2024-02-12'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForDatetimeField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            DateFieldBuilder::aDateField(253)
                ->withName(self::FIELD_NAME)
                ->withTime()
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            DateFieldBuilder::aDateField(751)
                ->withName(self::FIELD_NAME)
                ->withTime()
                ->inTracker($this->second_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('2024-02-12 10:25'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForStaticListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(746)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                SelectboxFieldBuilder::aSelectboxField(466)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('my_value'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForUGroupListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListUserGroupBindBuilder::aUserGroupBind(
                SelectboxFieldBuilder::aSelectboxField(457)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserGroupBindBuilder::aUserGroupBind(
                SelectboxFieldBuilder::aSelectboxField(624)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('Project members'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsSQLForUserListField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ListUserBindBuilder::aUserBind(
                SelectboxFieldBuilder::aSelectboxField(832)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserBindBuilder::aUserBind(
                SelectboxFieldBuilder::aSelectboxField(156)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('Fred'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsEmptySQLForInvalidDuckTypedField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            ExternalFieldBuilder::anExternalField(231)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build()
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper(5));
        self::assertEmpty($from_where->getFrom());
        self::assertEmpty($from_where->getWhere());
        self::assertEmpty($from_where->getFromParameters());
        self::assertEmpty($from_where->getWhereParameters());
    }
}
