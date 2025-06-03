<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use ReflectionClass;
use TestHelper;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Field_Integer;
use Tracker_Report_Criteria;
use Tuleap\GlobalResponseMock;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerValueDao;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_IntegerTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;

    public function testNoDefaultValue(): void
    {
        $int_field = IntFieldBuilder::anIntField(456)->build();
        self::assertFalse($int_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $int_field = IntFieldBuilder::anIntField(456)->withSpecificProperty('default_value', ['value' => 12])->build();
        self::assertTrue($int_field->hasDefaultValue());
        self::assertEquals(12, $int_field->getDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = $this->createMock(IntegerValueDao::class);

        $result = ['id' => 123, 'field_id' => 1, 'value' => '42'];
        $value_dao->method('searchById')->willReturn(TestHelper::arrayToDar($result));

        $integer_field = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['getValueDao']);
        $integer_field->method('getValueDao')->willReturn($value_dao);

        self::assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Integer::class,
            $integer_field->getChangesetValue(ChangesetTestBuilder::aChangeset(123)->build(), 123, false)
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = $this->createMock(IntegerValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::arrayToDar([]));

        $integer_field = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['getValueDao']);
        $integer_field->method('getValueDao')->willReturn($value_dao);

        self::assertNull($integer_field->getChangesetValue(ChangesetTestBuilder::aChangeset(123)->build(), 123, false));
    }

    public function testIsValidRequiredField(): void
    {
        $field    = IntFieldBuilder::anIntField(456)->thatIsRequired()->build();
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();
        self::assertTrue($field->isValid($artifact, 2));
        self::assertTrue($field->isValid($artifact, 789));
        self::assertTrue($field->isValid($artifact, -1));
        self::assertTrue($field->isValid($artifact, 0));
        self::assertTrue($field->isValid($artifact, '56'));
        self::assertFalse($field->isValid($artifact, 'toto'));
        self::assertFalse($field->isValid($artifact, '12toto'));
        self::assertFalse($field->isValid($artifact, 1.23));
        self::assertFalse($field->isValid($artifact, []));
        self::assertFalse($field->isValid($artifact, [1]));
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, ''));
        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $field    = IntFieldBuilder::anIntField(456)->build();
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();
        self::assertTrue($field->isValid($artifact, ''));
        self::assertTrue($field->isValid($artifact, null));
    }

    public function testGetFieldData(): void
    {
        $field = IntFieldBuilder::anIntField(456)->build();
        self::assertEquals('42', $field->getFieldData('42'));
    }

    public function testBuildMatchExpression(): void
    {
        $field      = IntFieldBuilder::anIntField(456)->build();
        $reflection = new ReflectionClass($field::class);
        $method     = $reflection->getMethod('buildMatchExpression');
        $method->setAccessible(true);

        self::assertFragment('field = ?', [12], $method->invokeArgs($field, ['field', '12']));
        self::assertFragment('field < ?', [12], $method->invokeArgs($field, ['field', '<12']));
        self::assertFragment('field <= ?', [12], $method->invokeArgs($field, ['field', '<=12']));
        self::assertFragment('field > ?', [12], $method->invokeArgs($field, ['field', '>12']));
        self::assertFragment('field >= ?', [12], $method->invokeArgs($field, ['field', '>=12']));
        self::assertFragment('field >= ? AND field <= ?', [12, 34], $method->invokeArgs($field, ['field', '12-34']));
        self::assertTrue($method->invokeArgs($field, ['field', ' <12'])->isNothing()); //Invalid syntax, we don't search against this field
        self::assertTrue($method->invokeArgs($field, ['field', '<=toto'])->isNothing()); //Invalid syntax, we don't search against this field
    }

    /**
     * @param Option<ParametrizedSQLFragment> $fragment
     */
    private function assertFragment(string $expected_sql, array $expected_parameters, Option $fragment): void
    {
        $fragment = $fragment->unwrapOr(null);
        if ($fragment === null) {
            self::fail('Does not match expected ' . $expected_sql);
        }

        self::assertEquals($expected_sql, $fragment->sql);
        self::assertEquals($expected_parameters, $fragment->parameters);
    }

    public function testItSearchOnZeroValue(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['isUsed', 'getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->method('isUsed')->willReturn(true);
        $field->method('getCriteriaValue')->willReturn(0);

        self::assertFalse($field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItSearchOnCustomQuery(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['isUsed', 'getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->method('isUsed')->willReturn(true);
        $field->method('getCriteriaValue')->willReturn('>1');

        self::assertFalse($field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItDoesntSearchOnEmptyString(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['isUsed', 'getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->method('isUsed')->willReturn(true);
        $field->method('getCriteriaValue')->willReturn('');

        self::assertTrue($field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItDoesntSearchOnNullCriteria(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['isUsed', 'getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->method('isUsed')->willReturn(true);
        $field->method('getCriteriaValue')->willReturn(null);

        self::assertTrue($field->getCriteriaFromWhere($criteria)->isNothing());
    }

    public function testItFetchCriteriaAndSetValueZero(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->setId(1);
        $field->method('getCriteriaValue')->willReturn(0);

        self::assertEquals(
            '<input data-test="integer-report-criteria" type="text" name="criteria[1]" id="tracker_report_criteria_1" value="0" />',
            $field->fetchCriteriaValue($criteria)
        );
    }

    public function testItFetchCriteriaAndLeaveItEmptyValue(): void
    {
        $field    = $this->createPartialMock(Tracker_FormElement_Field_Integer::class, ['getCriteriaValue']);
        $criteria = new Tracker_Report_Criteria(1, ReportTestBuilder::aPublicReport()->build(), $field, 1, false);

        $field->setId(1);
        $field->method('getCriteriaValue')->willReturn('');

        self::assertEquals(
            '<input data-test="integer-report-criteria" type="text" name="criteria[1]" id="tracker_report_criteria_1" value="" />',
            $field->fetchCriteriaValue($criteria)
        );
    }

    public function itReturnsTheValueIndexedByFieldName(): void
    {
        $field = IntFieldBuilder::anIntField(873)->build();
        $value = [
            'field_id' => 873,
            'value'    => 42,
        ];

        self::assertEquals(42, $field->getFieldDataFromRESTValueByField($value));
    }
}
