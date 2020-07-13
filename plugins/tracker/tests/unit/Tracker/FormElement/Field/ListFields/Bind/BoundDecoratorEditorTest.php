<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class BoundDecoratorEditorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $field_id;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_List
     */
    private $field;
    /**
     * @var BoundDecoratorEditor
     */
    private $bound_decorator_editor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_List_BindDecoratorDao
     */
    private $bind_decorator_dao;

    protected function setUp(): void
    {
        $this->bind_decorator_dao    = \Mockery::mock(\Tracker_FormElement_Field_List_BindDecoratorDao::class);
        $this->bound_decorator_editor = new BoundDecoratorEditor($this->bind_decorator_dao);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->field_id = 101;
        $this->field->shouldReceive('getId')->andReturn($this->field_id);
    }

    public function testItHasSpecificEditForLegacyColor(): void
    {
        $this->bind_decorator_dao->shouldReceive('updateColor')->with(1024, 255, 255, 255)->once();
        $this->bound_decorator_editor->update($this->field, 1024, "#FFFFFF", false);
    }

    public function testItHasSpecificEditForNoneLegacyColor(): void
    {
        $this->bind_decorator_dao->shouldReceive('updateNoneLegacyColor')
            ->with($this->field_id, 255, 255, 255)->once();
        $this->bound_decorator_editor->update($this->field, \Tracker_FormElement_Field_List::NONE_VALUE, "#FFFFFF", false);
    }

    public function testItHasSpecificEditForTlpColor(): void
    {
        $this->bind_decorator_dao->shouldReceive('updateTlpColor')->with(1024, "peggy-pink")->once();
        $this->bound_decorator_editor->update($this->field, 1024, "peggy-pink", false);
    }

    public function testItHasSpecificEditForNoneTlpColor(): void
    {
        $this->bind_decorator_dao->shouldReceive('updateNoneTlpColor')
            ->with($this->field_id, "peggy-pink")->once();
        $this->bound_decorator_editor->update($this->field, \Tracker_FormElement_Field_List::NONE_VALUE, "peggy-pink", false);
    }

    public function testItDeleteExistingNoneColorWhenFieldIsRequired(): void
    {
        $this->bind_decorator_dao->shouldReceive('delete')
            ->with($this->field_id, \Tracker_FormElement_Field_List::NONE_VALUE)->once();
        $this->bound_decorator_editor->update(
            $this->field,
            \Tracker_FormElement_Field_List::NONE_VALUE,
            "#FFFFFF",
            true
        );
    }
}
