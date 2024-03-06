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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\LegacyTabTranslationsSupport;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
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

final class ListFieldCheckerWithBindUsersTest extends TestCase
{
    use LegacyTabTranslationsSupport;

    private const FIELD_NAME = 'a_field';
    private const USER_NAME  = 'admin';
    private Comparison $comparison;

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

        $field     = CheckboxFieldBuilder::aCheckboxField(714)->withName(self::FIELD_NAME)->build();
        $user_bind = $this->createStub(\Tracker_FormElement_Field_List_Bind_Users::class);
        $user_bind->method('getField')->willReturn($field);
        $user_bind->method('accept')->willReturnCallback(
            fn(BindVisitor $visitor, BindParameters $parameters) => $visitor->visitListBindUsers(
                $user_bind,
                $parameters
            )
        );
        $field->setBind($user_bind);
        $user_bind->method('getAllValues')->willReturn([
            new \Tracker_FormElement_Field_List_Bind_UsersValue(117, self::USER_NAME),
            new \Tracker_FormElement_Field_List_Bind_UsersValue(107, 'Wai Lei'),
        ]);
        $checker->checkFieldIsValidForComparison($this->comparison, $field);
    }

    public function testItDoesNotThrowWhenEmptyValueIsAllowed(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(''));
        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItDoesNotThrowWhenValueExists(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper(self::USER_NAME));
        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItDoesNotThrowWithMyselfValueAndCurrentUserIsLoggedIn(): void
    {
        $current_user     = UserTestBuilder::aUser()->withUserName(self::USER_NAME)->build();
        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($current_user))
        );

        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItThrowsWithMyselfValueAndCurrentUserIsAnonymous(): void
    {
        $anonymous        = UserTestBuilder::anAnonymousUser()->build();
        $this->comparison = new EqualComparison(
            new Field(self::FIELD_NAME),
            new CurrentUserValueWrapper(ProvideCurrentUserStub::buildWithUser($anonymous))
        );

        $this->expectException(ListToMySelfForAnonymousComparisonException::class);
        $this->check();
    }

    public function testItThrowsWhenValueDoesNotExist(): void
    {
        $this->comparison = new EqualComparison(new Field(self::FIELD_NAME), new SimpleValueWrapper('c'));
        $this->expectException(ListValueDoNotExistComparisonException::class);
        $this->check();
    }
}
