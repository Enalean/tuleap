<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;
use Tuleap\Tracker\FormElement\Field\ListField;

#[DisableReturnValueGenerationForTestDoubles]
final class ListFormElementTypeUpdaterTest extends TestCase
{
    use GlobalResponseMock;

    private const SIMPLE_LIST_ELEMENT_ID = 20000;

    private ListFormElementTypeUpdater $updater;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private FieldDao&MockObject $field_dao;
    private BindDefaultValueDao&MockObject $bind_default_value_dao;

    protected function setUp(): void
    {
        $this->form_element_factory   = $this->createMock(Tracker_FormElementFactory::class);
        $this->field_dao              = $this->createMock(FieldDao::class);
        $this->bind_default_value_dao = $this->createMock(BindDefaultValueDao::class);

        $this->updater = new ListFormElementTypeUpdater(
            new DBTransactionExecutorPassthrough(),
            $this->form_element_factory,
            $this->field_dao,
            $this->bind_default_value_dao
        );
    }

    public function testItUpdatesFieldType(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);
        $form_element->expects($this->once())->method('getSharedTargets')->willReturn([]);

        $this->field_dao->expects($this->once())->method('setType')->with($form_element, 'msb')->willReturn(true);

        $this->form_element_factory->expects($this->once())->method('clearElementFromCache')->with($form_element);
        $this->form_element_factory->expects($this->once())->method('getFormElementById')->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->willReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->bind_default_value_dao->expects($this->never())->method('save');

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    public function testItClearsDefaultValuesWhenFieldIsChangedFromMultipleToSimpleList(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, true);
        $form_element->expects($this->once())->method('getSharedTargets')->willReturn([]);

        $this->field_dao->expects($this->once())->method('setType')->with($form_element, 'sb')->willReturn(true);

        $this->form_element_factory->expects($this->once())->method('clearElementFromCache')->with($form_element);
        $this->form_element_factory->expects($this->once())->method('getFormElementById')->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->willReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->bind_default_value_dao->expects($this->once())->method('save')->with(self::SIMPLE_LIST_ELEMENT_ID, []);

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $form_element,
            'sb'
        );
    }

    public function testItThrowsAnExceptionAndDoNotAskToUpdatesTargetFieldIfError(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);
        $form_element->expects($this->never())->method('getSharedTargets');

        $this->field_dao->expects($this->once())->method('setType')->with($form_element, 'msb')->willReturn(false);

        $this->expectException(FormElementTypeUpdateErrorException::class);

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    public function testItUpdatesFieldTypeWithTargets(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);

        $target_field_01_id = 20001;
        $target_field_01    = $this->getListFormElement($target_field_01_id, false);

        $target_field_02_id = 20002;
        $target_field_02    = $this->getListFormElement($target_field_02_id, false);

        $form_element->expects($this->once())->method('getSharedTargets')
            ->willReturn([$target_field_01, $target_field_02]);

        $this->field_dao->expects($this->exactly(3))->method('setType')
            ->with(
                self::callback(static fn(ListField $field) => in_array($field, [$form_element, $target_field_01, $target_field_02])),
                'msb',
            )
            ->willReturn(true);

        $this->form_element_factory->expects($this->exactly(3))->method('clearElementFromCache')
            ->with(self::callback(static fn(ListField $field) => in_array($field, [$form_element, $target_field_01, $target_field_02])));

        $this->form_element_factory->expects($this->exactly(3))->method('getFormElementById')
            ->willReturnCallback(fn(int $id) => match ($id) {
                self::SIMPLE_LIST_ELEMENT_ID => $this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false),
                $target_field_01_id          => $this->getUpdatedListFormElement($target_field_01_id, false),
                $target_field_02_id          => $this->getUpdatedListFormElement($target_field_02_id, false),
            });

        $this->bind_default_value_dao->expects($this->never())->method('save');

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    public function testItStopsUpdatesOfTargetFieldIfOneIsInError(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);

        $target_field_01_id = 20001;
        $target_field_01    = $this->getListFormElement($target_field_01_id, false);

        $target_field_02_id = 20002;
        $target_field_02    = $this->getListFormElement($target_field_02_id, false);

        $form_element->expects($this->once())->method('getSharedTargets')
            ->willReturn([$target_field_01, $target_field_02]);

        $this->field_dao->expects($this->exactly(3))->method('setType')
            ->with(self::anything(), 'msb')
            ->willReturnCallback(static fn(ListField $field) => $field !== $target_field_02);

        $this->form_element_factory->expects($this->exactly(2))->method('clearElementFromCache')
            ->with(self::callback(static fn(ListField $field) => in_array($field, [$form_element, $target_field_01])));

        $this->form_element_factory->expects($this->exactly(2))->method('getFormElementById')
            ->willReturnCallback(fn(int $id) => match ($id) {
                self::SIMPLE_LIST_ELEMENT_ID => $this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false),
                $target_field_01_id          => $this->getUpdatedListFormElement($target_field_01_id, false),
            });

        $this->bind_default_value_dao->expects($this->never())->method('save');

        $this->expectException(FormElementTypeUpdateErrorException::class);

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    private function getListFormElement(int $element_id, bool $is_multiple): ListField&MockObject
    {
        $mock = $this->createMock(ListField::class);
        $mock->method('getId')->willReturn($element_id);
        $mock->method('isMultiple')->willReturn($is_multiple);
        $mock->method('changeType')->willReturn(true);

        return $mock;
    }

    private function getUpdatedListFormElement(int $element_id, bool $is_multiple): ListField&MockObject
    {
        $mock = $this->getListFormElement($element_id, $is_multiple);
        $mock->expects($this->once())->method('getFlattenPropertiesValues')->willReturn([]);
        $mock->expects($this->once())->method('storeProperties');

        return $mock;
    }
}
