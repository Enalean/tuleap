<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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
 *
 */

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

final class Tracker_FormElement_Field_OpenListTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_OpenValueDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_Static
     */
    private $bind;

    /**
     * @var \Mockery\Mock | Tracker_FormElement_Field_OpenList
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = Mockery::mock(Tracker_FormElement_Field_OpenList::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->dao   = Mockery::mock(Tracker_FormElement_Field_List_OpenValueDao::class);

        $this->field->shouldReceive('getBind')->andReturn($this->bind);
        $this->field->shouldReceive('getOpenValueDao')->andReturn($this->dao);
    }

    public function testGetChangesetValue(): void
    {
        $open_value_dao = Mockery::mock(Tracker_FormElement_Field_Value_OpenListDao::class);
        $open_value_dao->shouldReceive('searchById')->andReturn(
            TestHelper::arrayToDar(
                ['id' => '10', 'field_id' => '1', 'label' => 'Open_1'],
                ['id' => '10', 'field_id' => '1', 'label' => 'Open_2']
            )
        );

        $open_value_dao->shouldReceive('searchById')->andReturn([1, '10']); //, $odar_10,
        $open_value_dao->shouldReceive('searchById')->andReturn([1, '20']); //, $odar_20,

        $value_dao = Mockery::mock(Tracker_FormElement_Field_Value_OpenListDao::class);
        $results = TestHelper::arrayToDar(
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000', 'openvalue_id' => null],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001', 'openvalue_id' => null],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '10'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '20'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002', 'openvalue_id' => null]
        );

        $value_dao->shouldReceive('searchById')->andReturn($results);

        $bind_values = [
            Mockery::mock(Tracker_FormElement_Field_List_BindValue::class),
            Mockery::mock(Tracker_FormElement_Field_List_BindValue::class),
            Mockery::mock(Tracker_FormElement_Field_List_BindValue::class),
        ];

        $bind = Mockery::mock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->shouldReceive('getBindValuesForIds')
            ->withArgs([[0 => '1000', 1 => '1001', 2 => '1002']])
            ->andReturn(
                [
                    '1000' => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class),
                    '1001' => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class),
                    '1002' => Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)
                ]
            );

        $list_field = Mockery::mock(Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $list_field->shouldReceive('getId')->andReturn(1);
        $list_field->shouldReceive('getValueDao')->andReturn($value_dao);
        $list_field->shouldReceive('getOpenValueDao')->andReturn($open_value_dao);
        $list_field->shouldReceive('getBind')->andReturn($bind);

        $changeset_value = $list_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false);
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_OpenList::class, $changeset_value);
        $this->assertCount(5, $changeset_value->getListValues());

        $list_values = $changeset_value->getListValues();
        $this->assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[0]);
        $this->assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[1]);
        $this->assertInstanceOf(Tracker_FormElement_Field_List_OpenValue::class, $list_values[2]);
        $this->assertInstanceOf(Tracker_FormElement_Field_List_OpenValue::class, $list_values[3]);
        $this->assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[4]);
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = Mockery::mock(Tracker_FormElement_Field_Value_OpenListDao::class);
        $value_dao->shouldReceive('searchbyId')->andReturn(TestHelper::arrayToDar());

        $list_field = Mockery::mock(Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $list_field->shouldReceive('getId')->andReturn(1);
        $list_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $changeset_value = $list_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false);
        $this->assertCount(0, $changeset_value->getListValues());
    }

    public function testSaveValue(): void
    {
        $artifact = null;
        $changeset_id = 666;
        $submitted_value = array();
        $submitted_value[] = 'b101';   //exisiting bind value
        $submitted_value[] = 'b102 ';  //existing bind value
        $submitted_value[] = ' o301';  //existing open value
        $submitted_value[] = 'o302';   //existing open value
        $submitted_value[] = 'b103';   //existing bind value
        $submitted_value[] = '';       //bidon
        $submitted_value[] = 'bidon';  //bidon
        $submitted_value[] = '!new_1'; //new open value
        $submitted_value[] = '!new_2'; //new open value
        $submitted_value = implode(',', $submitted_value);

        $open_value_dao = Mockery::mock(Tracker_FormElement_Field_Value_OpenListDao::class);
        $open_value_dao->shouldReceive('create')->andReturn(array(1, 'new_1'))->once()->andReturn(901);
        $open_value_dao->shouldReceive('create')->andReturn(array(1, 'new_2'))->once()->andReturn(902);

        $value_dao = Mockery::mock(Tracker_FormElement_Field_Value_OpenListDao::class);
        $value_dao->shouldReceive('create')->withArgs(
            [
                $changeset_id,
                [
                    ['bindvalue_id' => 101, 'openvalue_id' => null],
                    ['bindvalue_id' => 102, 'openvalue_id' => null],
                    ['bindvalue_id' => null, 'openvalue_id' => 301],
                    ['bindvalue_id' => null, 'openvalue_id' => 302],
                    ['bindvalue_id' => 103, 'openvalue_id' => null],
                    ['bindvalue_id' => null, 'openvalue_id' => 901],
                    ['bindvalue_id' => null, 'openvalue_id' => 902],
                ],
            ]
        )->once();

        $list_field = Mockery::mock(Tracker_FormElement_Field_OpenList::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $list_field->shouldReceive('getId')->andReturn(1);
        $list_field->shouldReceive('getValueDao')->andReturn($value_dao);
        $list_field->shouldReceive('getOpenValueDao')->andReturn($open_value_dao);

        $list_field->saveValue($artifact, $changeset_id, $submitted_value, null, Mockery::mock(CreatedFileURLMapping::class));
    }

    public function testItResetsTheFieldValueWhenSubmittedValueIsEmpty(): void
    {
        $this->assertEquals('', $this->field->getFieldData(''));
    }

    public function testItCreatesOneValue(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['new value', Mockery::any()])->andReturn(null)->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'new value'])->andReturn(TestHelper::arrayToDar())->once();

        $this->assertEquals('!new value', $this->field->getFieldData('new value'));
    }

    public function testItUsesOneValueDefinedByAdmin(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['existing value', Mockery::any()])->andReturn(115)->once();

        $this->dao->shouldReceive('searchByExactLabel')->never();

        $this->assertEquals('b115', $this->field->getFieldData('existing value'));
    }

    public function testItUsesOneOpenValueDefinedPreviously(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['existing open value', Mockery::any()])->andReturn(null)->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'existing open value'])
            ->andReturn(TestHelper::arrayToDar(['id' => '30', 'field_id' => '1', 'label' => 'existing open value']))
            ->once();

        $this->assertEquals('o30', $this->field->getFieldData('existing open value'));
    }

    public function testItCreatesTwoNewValues(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['new value', Mockery::any()])->andReturn(null)->once();
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['yet another new value', Mockery::any()])->andReturn(null)->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'new value'])
            ->andReturn(TestHelper::arrayToDar([]))
            ->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'yet another new value'])
            ->andReturn(TestHelper::arrayToDar([]))
            ->once();

        $this->assertEquals('!new value,!yet another new value', $this->field->getFieldData('new value,yet another new value'));
    }

    public function testItCreatesANewValueAndReuseABindValueSetByAdmin(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['new value', Mockery::any()])->andReturn(null)->once();
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['existing value', Mockery::any()])->andReturn(115)->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'new value'])
            ->andReturn(TestHelper::arrayToDar([]))
            ->once();

        $this->assertEquals('!new value,b115', $this->field->getFieldData('new value,existing value'));
    }

    public function testItCreatesANewValueAndReuseABindValueAndCreatesAnOpenValue(): void
    {
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['new value', Mockery::any()])->andReturn(null)->once();
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['existing open value', Mockery::any()])->andReturn(null)->once();
        $this->bind->shouldReceive('getFieldData')
            ->withArgs(['existing value', Mockery::any()])->andReturn(115)->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'new value'])
            ->andReturn(TestHelper::arrayToDar([]))
            ->once();

        $this->dao->shouldReceive('searchByExactLabel')
            ->withArgs([Mockery::any(), 'existing open value'])
            ->andReturn(TestHelper::arrayToDar(['id' => '30', 'field_id' => '1', 'label' => 'existing open value']))
            ->once();

        $this->assertEquals('!new value,o30,b115', $this->field->getFieldData('new value,existing open value,existing value'));
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = new Tracker_FormElement_Field_OpenList(
            1,
            101,
            null,
            'field_openlist',
            'Field OpenList',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }

    public function testItAcceptsValidValues(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $field = Mockery::mock(Tracker_FormElement_Field_OpenList::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('validate')->andReturnTrue();

        $this->assertTrue($field->isValid($artifact, ''));
        $this->assertTrue($field->isValid($artifact, 'b101'));
        $this->assertTrue(
            $field->isValid(
                $artifact,
                Tracker_FormElement_Field_OpenList::BIND_PREFIX . Tracker_FormElement_Field_OpenList::NONE_VALUE
            )
        );
        $this->assertTrue($field->isValid($artifact, ['b101', 'b102']));
    }
}
