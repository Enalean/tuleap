<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use BaseLanguageFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\CollectionOfNormalizedBindLabelsExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToEmptyStringTermException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToMySelfForAnonymousComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToNowComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListToStatusOpenComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ListValueDoNotExistComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\ListFieldBindValueNormalizer;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Test\Builders\Fields\CheckboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;

final class ListFieldCheckerWithBindUgroupsTest extends TestCase
{
    use GlobalLanguageMock;
    use LegacyTabTranslationsSupport;

    private const FIELD_NAME = 'a_field';
    private Comparison $comparison;

    protected function setUp(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->willReturnCallback(static fn ($key1, $key2) => match ($key2) {
                'ugroup_project_members' => 'Project Members',
            });
    }

    /**
     * @throws ListValueDoNotExistComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws FieldIsNotSupportedForComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws ListToEmptyStringTermException
     */
    private function check(): void
    {
        $list_field_bind_value_normalizer = new ListFieldBindValueNormalizer();
        $ugroup_label_converter           = new UgroupLabelConverter(
            $list_field_bind_value_normalizer,
            new BaseLanguageFactory()
        );
        $checker                          = new ListFieldChecker(
            $list_field_bind_value_normalizer,
            new CollectionOfNormalizedBindLabelsExtractor(
                $list_field_bind_value_normalizer,
                $ugroup_label_converter
            ),
            $ugroup_label_converter
        );
        $checker->checkFieldIsValidForComparison(
            $this->comparison,
            ListUserGroupBindBuilder::aUserGroupBind(
                CheckboxFieldBuilder::aCheckboxField(714)->withName(self::FIELD_NAME)->build()
            )->withUserGroups([
                ProjectUGroupTestBuilder::buildProjectMembers(),
                ProjectUGroupTestBuilder::aCustomUserGroup(704)->withName('Mountaineers')->build(),
            ])->build()->getField()
        );
    }

    public function testItDoesNotThrowWhenDynamicUgroupIsInValuesAndIsSupported(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('Project Members'));
        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItDoesNotThrowWhenStaticUgroupIsInValues(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('MOUNTAINEERS'));
        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItThrowsWhenStaticUgroupIsNotInValues(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('herbaceous'));
        $this->expectException(ListValueDoNotExistComparisonException::class);
        $this->check();
    }

    public function testItThrowsWhenDynamicUgroupIsNotInValues(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('Project Administrators'));
        $this->expectException(ListValueDoNotExistComparisonException::class);
        $this->check();
    }

    public function testItThrowsWhenDynamicUgroupIsNotSupported(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('Registered users'));
        $this->expectException(ListValueDoNotExistComparisonException::class);
        $this->check();
    }
}
