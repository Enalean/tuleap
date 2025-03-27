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

use Mockery;
use Mockery\MockInterface;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListFormElementTypeUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    private const SIMPLE_LIST_ELEMENT_ID = 20000;

    /**
     * @var ListFormElementTypeUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldDao
     */
    private $field_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BindDefaultValueDao
     */
    private $bind_default_value_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory   = Mockery::mock(\Tracker_FormElementFactory::instance());
        $this->field_dao              = Mockery::mock(FieldDao::class);
        $this->bind_default_value_dao = Mockery::mock(BindDefaultValueDao::class);

        $this->updater = new ListFormElementTypeUpdater(
            new DBTransactionExecutorPassthrough(),
            $this->form_element_factory,
            $this->field_dao,
            $this->bind_default_value_dao
        );
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);
    }

    public function testItUpdatesFieldType(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);
        $form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([]);

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($form_element, 'msb')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($form_element);
        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->andReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->bind_default_value_dao->shouldReceive('save')->never();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    public function testItClearsDefaultValuesWhenFieldIsChangedFromMultipleToSimpleList(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, true);
        $form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([]);

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($form_element, 'sb')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($form_element);
        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->andReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->bind_default_value_dao->shouldReceive('save')->once()->with(self::SIMPLE_LIST_ELEMENT_ID, []);

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $form_element,
            'sb'
        );
    }

    public function testItThrowsAnExceptionAndDoNotAskToUpdatesTargetFieldIfError(): void
    {
        $form_element = $this->getListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false);
        $form_element->shouldNotReceive('getSharedTargets');

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($form_element, 'msb')
            ->andReturnFalse();

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

        $form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([
                $target_field_01,
                $target_field_02,
            ]);

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($form_element, 'msb')
            ->andReturnTrue();

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($target_field_01, 'msb')
            ->andReturnTrue();

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($target_field_02, 'msb')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($form_element);
        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($target_field_01);
        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($target_field_02);

        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->andReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with($target_field_01_id)
            ->andReturn($this->getUpdatedListFormElement($target_field_01_id, false));

        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with($target_field_02_id)
            ->andReturn($this->getUpdatedListFormElement($target_field_02_id, false));

        $this->bind_default_value_dao->shouldReceive('save')->never();

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

        $form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([
                $target_field_01,
                $target_field_02,
            ]);

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($form_element, 'msb')
            ->andReturnTrue();

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($target_field_01, 'msb')
            ->andReturnTrue();

        $this->field_dao->shouldReceive('setType')
            ->once()
            ->with($target_field_02, 'msb')
            ->andReturnFalse();

        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($form_element);
        $this->form_element_factory->shouldReceive('clearElementFromCache')->once()->with($target_field_01);

        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with(self::SIMPLE_LIST_ELEMENT_ID)
            ->andReturn($this->getUpdatedListFormElement(self::SIMPLE_LIST_ELEMENT_ID, false));

        $this->form_element_factory->shouldReceive('getFormElementById')
            ->once()
            ->with($target_field_01_id)
            ->andReturn($this->getUpdatedListFormElement($target_field_01_id, false));

        $this->bind_default_value_dao->shouldReceive('save')->never();

        $this->expectException(FormElementTypeUpdateErrorException::class);

        $this->updater->updateFormElementType(
            $form_element,
            'msb'
        );
    }

    /**
     * @return Mockery\LegacyMockInterface&Mockery\MockInterface&\Tracker_FormElement_Field_List
     */
    private function getListFormElement(int $element_id, bool $is_multiple)
    {
        $mock = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $mock->shouldReceive('getId')->andReturn($element_id);
        $mock->shouldReceive('isMultiple')->andReturn($is_multiple);
        $mock->shouldReceive('changeType')->andReturnTrue();

        return $mock;
    }

    /**
     * @return Mockery\LegacyMockInterface&MockInterface&\Tracker_FormElement_Field_List
     */
    private function getUpdatedListFormElement(int $element_id, bool $is_multiple)
    {
        $mock = $this->getListFormElement($element_id, $is_multiple);
        $mock->shouldReceive('getFlattenPropertiesValues')->once()->andReturn([]);
        $mock->shouldReceive('storeProperties')->once();

        return $mock;
    }
}
