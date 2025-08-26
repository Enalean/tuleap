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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\List;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use TestHelper;
use Tracker_Artifact_ChangesetValue_OpenList;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListChangesetValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class OpenListFieldTest extends TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private OpenListValueDao&MockObject $dao;
    private Tracker_FormElement_Field_List_Bind_Static&MockObject $bind;
    private OpenListField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = $this->createPartialMock(OpenListField::class, ['getId', 'getBind', 'getOpenValueDao']);
        $this->bind  = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $this->dao   = $this->createMock(OpenListValueDao::class);

        $this->field->method('getId')->willReturn(852);
        $this->field->method('getBind')->willReturn($this->bind);
        $this->field->method('getOpenValueDao')->willReturn($this->dao);
    }

    public function testGetChangesetValue(): void
    {
        $open_value_dao = $this->createMock(OpenListChangesetValueDao::class);
        $open_value_dao->method('searchById')->willReturn(
            TestHelper::arrayToDar(
                ['id' => '10', 'field_id' => '1', 'label' => 'Open_1', 'is_hidden' => false],
                ['id' => '10', 'field_id' => '1', 'label' => 'Open_2', 'is_hidden' => false]
            )
        );

        $value_dao = $this->createMock(OpenListChangesetValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::arrayToDar(
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1000', 'openvalue_id' => null],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1001', 'openvalue_id' => null],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '10'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => null, 'openvalue_id' => '20'],
            ['id' => '123', 'field_id' => '1', 'bindvalue_id' => '1002', 'openvalue_id' => null]
        ));

        $bind_values = [
            ListStaticValueBuilder::aStaticValue('a')->build(),
            ListStaticValueBuilder::aStaticValue('b')->build(),
            ListStaticValueBuilder::aStaticValue('c')->build(),
        ];

        $bind = $this->getMockBuilder(Tracker_FormElement_Field_List_Bind_Static::class)
            ->setConstructorArgs([new DatabaseUUIDV7Factory(), $this->aRequiredOpenListField(), true, [], $bind_values, []])
            ->onlyMethods(['getBindValuesForIds'])
            ->getMock();
        $bind->method('getBindValuesForIds')->with([0 => '1000', 1 => '1001', 2 => '1002'])
            ->willReturn([
                '1000' => ListStaticValueBuilder::aStaticValue('1000')->build(),
                '1001' => ListStaticValueBuilder::aStaticValue('1001')->build(),
                '1002' => ListStaticValueBuilder::aStaticValue('1002')->build(),
            ]);

        $list_field = $this->createPartialMock(OpenListField::class, ['getId', 'getValueDao', 'getOpenValueDao', 'getBind']);
        $list_field->method('getId')->willReturn(1);
        $list_field->method('getValueDao')->willReturn($value_dao);
        $list_field->method('getOpenValueDao')->willReturn($open_value_dao);
        $list_field->method('getBind')->willReturn($bind);

        $changeset_value = $list_field->getChangesetValue(ChangesetTestBuilder::aChangeset(45)->build(), 123, false);
        self::assertInstanceOf(Tracker_Artifact_ChangesetValue_OpenList::class, $changeset_value);
        self::assertCount(5, $changeset_value->getListValues());

        $list_values = $changeset_value->getListValues();
        self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[0]);
        self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[1]);
        self::assertInstanceOf(Tracker_FormElement_Field_List_OpenValue::class, $list_values[2]);
        self::assertInstanceOf(Tracker_FormElement_Field_List_OpenValue::class, $list_values[3]);
        self::assertInstanceOf(Tracker_FormElement_Field_List_BindValue::class, $list_values[4]);
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = $this->createMock(OpenListChangesetValueDao::class);
        $value_dao->method('searchbyId')->willReturn(TestHelper::arrayToDar());

        $list_field = $this->createPartialMock(OpenListField::class, ['getId', 'getValueDao']);
        $list_field->method('getId')->willReturn(1);
        $list_field->method('getValueDao')->willReturn($value_dao);

        $changeset_value = $list_field->getChangesetValue(ChangesetTestBuilder::aChangeset(45)->build(), 123, false);
        self::assertCount(0, $changeset_value->getListValues());
    }

    public function testSaveValue(): void
    {
        $artifact          = null;
        $changeset_id      = 666;
        $submitted_value   = [];
        $submitted_value[] = 'b101';   //exisiting bind value
        $submitted_value[] = 'b102 ';  //existing bind value
        $submitted_value[] = ' o301';  //existing open value
        $submitted_value[] = 'o302';   //existing open value
        $submitted_value[] = 'b103';   //existing bind value
        $submitted_value[] = '';       //bidon
        $submitted_value[] = 'bidon';  //bidon
        $submitted_value[] = '!new_1'; //new open value
        $submitted_value[] = '!new_2'; //new open value
        $submitted_value   = implode(',', $submitted_value);

        $open_value_dao = $this->createMock(OpenListChangesetValueDao::class);
        $open_value_dao->expects($this->exactly(2))->method('create')->with(1, self::isString())
            ->willReturnCallback(static fn(int $changeset_id, string $label) => match ($label) {
                'new_1' => 901,
                'new_2' => 902,
            });

        $value_dao = $this->createMock(OpenListChangesetValueDao::class);
        $value_dao->expects($this->once())->method('create')->with(
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
        );

        $list_field = $this->createPartialMock(OpenListField::class, ['getId', 'getValueDao', 'getOpenValueDao']);
        $list_field->method('getId')->willReturn(1);
        $list_field->method('getValueDao')->willReturn($value_dao);
        $list_field->method('getOpenValueDao')->willReturn($open_value_dao);
        $reflection = new ReflectionClass($list_field::class);
        $method     = $reflection->getMethod('saveValue');
        $method->setAccessible(true);

        $method->invoke($list_field, $artifact, $changeset_id, $submitted_value, null, new CreatedFileURLMapping());
    }

    public function testItResetsTheFieldValueWhenSubmittedValueIsEmpty(): void
    {
        self::assertEquals('', $this->field->getFieldData(''));
    }

    public function testItCreatesOneValue(): void
    {
        $this->bind->expects($this->once())->method('getFieldData')
            ->with('new value', self::anything())->willReturn(null);

        $this->dao->expects($this->once())->method('searchByExactLabel')
            ->with(self::anything(), 'new value')->willReturn(TestHelper::arrayToDar());

        self::assertEquals('!new value', $this->field->getFieldData('new value'));
    }

    public function testItUsesOneValueDefinedByAdmin(): void
    {
        $this->bind->expects($this->once())->method('getFieldData')
            ->with('existing value', self::anything())->willReturn(115);

        $this->dao->expects($this->never())->method('searchByExactLabel');

        self::assertEquals('b115', $this->field->getFieldData('existing value'));
    }

    public function testItUsesOneOpenValueDefinedPreviously(): void
    {
        $this->bind->expects($this->once())->method('getFieldData')
            ->with('existing open value', self::anything())->willReturn(null);

        $this->dao->expects($this->once())->method('searchByExactLabel')
            ->with(self::anything(), 'existing open value')
            ->willReturn(TestHelper::arrayToDar(['id' => '30', 'field_id' => '1', 'label' => 'existing open value']));

        self::assertEquals('o30', $this->field->getFieldData('existing open value'));
    }

    public function testItCreatesTwoNewValues(): void
    {
        $this->bind->expects($this->exactly(2))->method('getFieldData')
            ->willReturnCallback(static fn(string $value) => match ($value) {
                'new value', 'yet another new value' => null,
            });

        $this->dao->expects($this->exactly(2))->method('searchByExactLabel')
            ->willReturnCallback(static fn(int $id, string $label) => match ($label) {
                'new value', 'yet another new value' => TestHelper::emptyDar(),
            });

        self::assertEquals('!new value,!yet another new value', $this->field->getFieldData('new value,yet another new value'));
    }

    public function testItCreatesANewValueAndReuseABindValueSetByAdmin(): void
    {
        $this->bind->expects($this->exactly(2))->method('getFieldData')
            ->willReturnCallback(static fn(string $value) => match ($value) {
                'new value'      => null,
                'existing value' => 115,
            });

        $this->dao->expects($this->once())->method('searchByExactLabel')
            ->with(self::anything(), 'new value')->willReturn(TestHelper::arrayToDar([]));

        self::assertEquals('!new value,b115', $this->field->getFieldData('new value,existing value'));
    }

    public function testItCreatesANewValueAndReuseABindValueAndCreatesAnOpenValue(): void
    {
        $this->bind->expects($this->exactly(3))->method('getFieldData')
            ->willReturnCallback(static fn(string $value) => match ($value) {
                'new value', 'existing open value' => null,
                'existing value'                   => 115,
            });

        $this->dao->expects($this->exactly(2))->method('searchByExactLabel')
            ->willReturnCallback(static fn(int $id, string $label) => match ($label) {
                'new value'           => TestHelper::emptyDar(),
                'existing open value' => TestHelper::arrayToDar(['id' => '30', 'field_id' => '1', 'label' => 'existing open value']),
            });

        self::assertEquals('!new value,o30,b115', $this->field->getFieldData('new value,existing open value,existing value'));
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = new OpenListField(
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
        $artifact = ArtifactTestBuilder::anArtifact(963)->build();
        $field    = $this->createPartialMock(OpenListField::class, ['validate']);
        $field->method('validate')->willReturn(true);

        self::assertTrue($field->isValid($artifact, ''));
        self::assertTrue($field->isValid($artifact, 'b101'));
        self::assertTrue(
            $field->isValid(
                $artifact,
                OpenListField::BIND_PREFIX . ListField::NONE_VALUE
            )
        );
        self::assertTrue($field->isValid($artifact, ['b101', 'b102']));
    }

    public function testWhenFieldIsRequiredItDoesNotAcceptInvalidValues(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $field    = $this->aRequiredOpenListField();

        $GLOBALS['Response']->method('addFeedback')
            ->with(
                'error',
                self::callback(static fn(string $message) => $message === 'Invalid value dummytext for field My Open List (openlist).' || $message === 'The field My Open List (openlist) is required.'),
            );

        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, 'dummytext'));
    }

    public function testWhenFieldIsRequiredItDoesNotAcceptInvalidBindOrOpenValues(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $field    = $this->aRequiredOpenListField();

        $GLOBALS['Response']
            ->method('addFeedback')
            ->with('error', 'The field My Open List (openlist) is required.');

        self::assertFalse($field->isValidRegardingRequiredProperty($artifact, 'bdummy,otext'));
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['b102'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['o102'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['!new value'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['!0'])]
    public function testWhenFieldIsRequiredItAcceptsRegularValue(string $value): void
    {
        $artifact = $this->createMock(Artifact::class);
        $field    = $this->aRequiredOpenListField();

        self::assertTrue($field->isValidRegardingRequiredProperty($artifact, $value));
    }

    private function aRequiredOpenListField(): OpenListField
    {
        return new OpenListField(
            102,
            10,
            1,
            'openlist',
            'My Open List',
            '',
            true,
            'P',
            true,
            'null',
            1,
            null
        );
    }
}
