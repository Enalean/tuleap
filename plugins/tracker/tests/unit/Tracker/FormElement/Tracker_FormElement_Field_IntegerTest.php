<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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

class Tracker_FormElement_Field_IntegerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @return \Mockery\Mock | Tracker_FormElement_Field_Integer
     */
    private function getIntegerField()
    {
        return Mockery::mock(Tracker_FormElement_Field_Integer::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testNoDefaultValue(): void
    {
        $int_field = $this->getIntegerField();
        $int_field->shouldReceive('getProperty')->withArgs(['default_value']);
        $this->assertFalse($int_field->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $int_field = $this->getIntegerField();
        $int_field->shouldReceive('getProperty')->withArgs(['default_value'])->andReturn('12');
        $this->assertTrue($int_field->hasDefaultValue());
        $this->assertEquals(12, $int_field->getDefaultValue());
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = Mockery::mock(Tracker_FormElement_Field_Value_IntegerDao::class);

        $result = array('id' => 123, 'field_id' => 1, 'value' => '42');
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::arrayToDar($result));

        $integer_field = $this->getIntegerField();
        $integer_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Integer::class,
            $integer_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false)
        );
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = Mockery::mock(Tracker_FormElement_Field_Value_IntegerDao::class);
        $value_dao->shouldReceive('searchById')->andReturn(TestHelper::arrayToDar([]));

        $integer_field = $this->getIntegerField();
        $integer_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertNull($integer_field->getChangesetValue(null, 123, false));
    }

    public function testIsValidRequiredField(): void
    {
        $f = $this->getIntegerField();
        $f->shouldReceive('isRequired')->andReturn(true);
        $a = Mockery::mock(Tracker_Artifact::class);
        $this->assertTrue($f->isValid($a, 2));
        $this->assertTrue($f->isValid($a, 789));
        $this->assertTrue($f->isValid($a, -1));
        $this->assertTrue($f->isValid($a, 0));
        $this->assertTrue($f->isValid($a, '56'));
        $this->assertFalse($f->isValid($a, 'toto'));
        $this->assertFalse($f->isValid($a, '12toto'));
        $this->assertFalse($f->isValid($a, 1.23));
        $this->assertFalse($f->isValid($a, array()));
        $this->assertFalse($f->isValid($a, array(1)));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, ''));
        $this->assertFalse($f->isValidRegardingRequiredProperty($a, null));
    }

    public function testIsValidNotRequiredField(): void
    {
        $f = $this->getIntegerField();
        $f->shouldReceive('isRequired')->andReturn(false);
        $a = Mockery::mock(Tracker_Artifact::class);
        $this->assertTrue($f->isValid($a, ''));
        $this->assertTrue($f->isValid($a, null));
    }

    public function testGetFieldData(): void
    {
        $f = $this->getIntegerField();
        $this->assertEquals('42', $f->getFieldData('42'));
    }

    public function testBuildMatchExpression(): void
    {
        $field = $this->getIntegerField();
        $reflection = new \ReflectionClass(get_class($field));
        $method     = $reflection->getMethod('buildMatchExpression');
        $method->setAccessible(true);

        $this->assertEquals('field = 12', $method->invokeArgs($field, ['field', '12']));
        $this->assertEquals('field < 12', $method->invokeArgs($field, ['field', '<12']));
        $this->assertEquals('field <= 12', $method->invokeArgs($field, ['field', '<=12']));
        $this->assertEquals('field > 12', $method->invokeArgs($field, ['field', '>12']));
        $this->assertEquals('field >= 12', $method->invokeArgs($field, ['field', '>=12']));
        $this->assertEquals('field >= 12 AND field <= 34', $method->invokeArgs($field, ['field', '12-34']));
        $this->assertEquals(1, $method->invokeArgs($field, ['field', ' <12'])); //Invalid syntax, we don't search against this field
        $this->assertEquals(1, $method->invokeArgs($field, ['field', '<=toto'])); //Invalid syntax, we don't search against this field
    }

    public function testItSearchOnZeroValue(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->shouldReceive('isUsed')->andReturn(true);
        $field->shouldReceive('getCriteriaValue')->andReturn(0);

        $this->assertNotEquals('', $field->getCriteriaFrom($criteria));
    }

    public function testItSearchOnCustomQuery(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->shouldReceive('isUsed')->andReturn(true);
        $field->shouldReceive('getCriteriaValue')->andReturn('>1');

        $this->assertNotEquals('', $field->getCriteriaFrom($criteria));
    }

    public function testItDoesntSearchOnEmptyString(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->shouldReceive('isUsed')->andReturn(true);
        $field->shouldReceive('getCriteriaValue')->andReturn('');

        $this->assertEquals('', $field->getCriteriaFrom($criteria));
    }

    public function testItDoesntSearchOnNullCriteria(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->shouldReceive('isUsed')->andReturn(true);
        $field->shouldReceive('getCriteriaValue')->andReturn(null);

        $this->assertEquals('', $field->getCriteriaFrom($criteria));
    }

    public function testItFetchCriteriaAndSetValueZero(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->setId(1);
        $field->shouldReceive('getCriteriaValue')->andReturn(0);

        $this->assertEquals(
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="0" />',
            $field->fetchCriteriaValue($criteria)
        );
    }

    public function testItFetchCriteriaAndLeaveItEmptyValue(): void
    {
        $field    = $this->getIntegerField();
        $criteria = $this->getCriteria();

        $field->setId(1);
        $field->shouldReceive('getCriteriaValue')->andReturn('');

        $this->assertEquals(
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="" />',
            $field->fetchCriteriaValue($criteria)
        );
    }

    public function itReturnsTheValueIndexedByFieldName(): void
    {
        $field = $this->getIntegerField();
        $value = [
            "field_id" => 873,
            "value"    => 42
        ];

        $this->assertEquals(42, $field->getFieldDataFromRESTValueByField($value));
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Report_Criteria
     */
    protected function getCriteria()
    {
        return Mockery::mock(Tracker_Report_Criteria::class);
    }
}
