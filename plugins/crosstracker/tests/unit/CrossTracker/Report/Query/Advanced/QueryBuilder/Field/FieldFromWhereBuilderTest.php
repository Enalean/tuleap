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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;

use BaseLanguageFactory;
use ForgeConfig;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\DateTimeValueRounder;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveFieldTypeStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

final class FieldFromWhereBuilderTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private const FIELD_NAME = 'my_field';
    private \PFUser $user;
    private \Tracker $first_tracker;
    private \Tracker $second_tracker;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');
        ForgeConfig::set('sys_lang', 'en_US');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        ForgeConfig::set('sys_incdir', __DIR__ . '/../../../../../../../../../../site-content');

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

        $date_time_value_rounder = new DateTimeValueRounder();
        $builder                 = new FieldFromWhereBuilder(
            $fields_retriever,
            RetrieveFieldTypeStub::withDetectionOfType(),
            new Numeric\NumericFromWhereBuilder(),
            new Text\TextFromWhereBuilder($db),
            new Date\DateFromWhereBuilder($date_time_value_rounder),
            new Datetime\DatetimeFromWhereBuilder($date_time_value_rounder),
            new StaticList\StaticListFromWhereBuilder(),
            new UGroupList\UGroupListFromWhereBuilder(
                new UgroupLabelConverter(new ListFieldBindValueNormalizer(), new BaseLanguageFactory()),
            ),
        );
        $field                   = new Field(self::FIELD_NAME);
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
            TrackerFormElementIntFieldBuilder::anIntField(134)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementIntFieldBuilder::anIntField(859)
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
            TrackerFormElementStringFieldBuilder::aStringField(209)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementStringFieldBuilder::aStringField(134)
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
            TrackerFormElementDateFieldBuilder::aDateField(125)
                ->withName(self::FIELD_NAME)
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementDateFieldBuilder::aDateField(334)
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
            TrackerFormElementDateFieldBuilder::aDateField(253)
                ->withName(self::FIELD_NAME)
                ->withTime()
                ->inTracker($this->first_tracker)
                ->withReadPermission($this->user, true)
                ->build(),
            TrackerFormElementDateFieldBuilder::aDateField(751)
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
                ListFieldBuilder::aListField(746)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListStaticBindBuilder::aStaticBind(
                ListFieldBuilder::aListField(466)
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
                ListFieldBuilder::aListField(457)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->first_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
            ListUserGroupBindBuilder::aUserGroupBind(
                ListFieldBuilder::aListField(624)
                    ->withName(self::FIELD_NAME)
                    ->inTracker($this->second_tracker)
                    ->withReadPermission($this->user, true)
                    ->build()
            )->build()->getField(),
        );

        $from_where = $this->getFromWhere($fields_retriever, new SimpleValueWrapper('Project members'));
        self::assertNotEmpty($from_where->getFrom());
    }

    public function testItReturnsEmptySQLForInvalidDuckTypedField(): void
    {
        $fields_retriever = RetrieveUsedFieldsStub::withFields(
            TrackerExternalFormElementBuilder::anExternalField(231)
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
