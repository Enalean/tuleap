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
use Tracker_FormElement;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class FormElementTypeUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var FormElementTypeUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement
     */
    private $form_element;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::instance());

        $this->updater = new FormElementTypeUpdater(
            new DBTransactionExecutorPassthrough(),
            $this->form_element_factory
        );

        $this->form_element = Mockery::mock(Tracker_FormElement::class);
    }

    public function testItUpdatesFieldType(): void
    {
        $this->form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([]);

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($this->form_element, 'new_type')
            ->andReturnTrue();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $this->form_element,
            'new_type'
        );
    }

    public function testItThrowsAnExceptionAndDoNotAskToUpdatesTargetFieldIfError(): void
    {
        $this->form_element->shouldNotReceive('getSharedTargets');

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($this->form_element, 'new_type')
            ->andReturnFalse();

        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('info');

        $this->expectException(FormElementTypeUpdateErrorException::class);

        $this->updater->updateFormElementType(
            $this->form_element,
            'new_type'
        );
    }

    public function testItUpdatesFieldTypeWithTargets(): void
    {
        $target_field_01 = Mockery::mock(Tracker_FormElement::class);
        $target_field_02 = Mockery::mock(Tracker_FormElement::class);

        $this->form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([
                $target_field_01,
                $target_field_02,
            ]);

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($this->form_element, 'new_type')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($target_field_01, 'new_type')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($target_field_02, 'new_type')
            ->andReturnTrue();

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('info');

        $this->updater->updateFormElementType(
            $this->form_element,
            'new_type'
        );
    }

    public function testItStopsUpdatesOfTargetFieldIfOneIsInError(): void
    {
        $target_field_01 = Mockery::mock(Tracker_FormElement::class);
        $target_field_02 = Mockery::mock(Tracker_FormElement::class);

        $this->form_element->shouldReceive('getSharedTargets')
            ->once()
            ->andReturn([
                $target_field_01,
                $target_field_02,
            ]);

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($this->form_element, 'new_type')
            ->andReturnTrue();

        $this->form_element_factory->shouldReceive('changeFormElementType')
            ->once()
            ->with($target_field_01, 'new_type')
            ->andReturnFalse();

        $this->form_element_factory->shouldNotReceive('changeFormElementType')
            ->with($target_field_02, 'new_type');

        $GLOBALS['Response']->expects(self::never())->method('addFeedback')->with('info');

        $this->expectException(FormElementTypeUpdateErrorException::class);

        $this->updater->updateFormElementType(
            $this->form_element,
            'new_type'
        );
    }
}
