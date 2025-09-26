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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BoundDecoratorEditorTest extends TestCase
{
    private const FIELD_ID = 101;

    private ListField $field;
    private BoundDecoratorEditor $bound_decorator_editor;
    private BindDecoratorDao&MockObject $bind_decorator_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->bind_decorator_dao     = $this->createMock(BindDecoratorDao::class);
        $this->bound_decorator_editor = new BoundDecoratorEditor($this->bind_decorator_dao);
        $this->field                  = SelectboxFieldBuilder::aSelectboxField(self::FIELD_ID)->build();
    }

    public function testItHasSpecificEditForLegacyColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('updateColor')->with(1024, 255, 255, 255);
        $this->bound_decorator_editor->update($this->field, 1024, '#FFFFFF', false);
    }

    public function testItHasSpecificEditForNoneLegacyColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('updateNoneLegacyColor')->with(self::FIELD_ID, 255, 255, 255);
        $this->bound_decorator_editor->update($this->field, ListField::NONE_VALUE, '#FFFFFF', false);
    }

    public function testItHasSpecificEditForTlpColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('updateTlpColor')->with(1024, 'peggy-pink');
        $this->bound_decorator_editor->update($this->field, 1024, 'peggy-pink', false);
    }

    public function testItHasSpecificEditForNoneTlpColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('updateNoneTlpColor')->with(self::FIELD_ID, 'peggy-pink');
        $this->bound_decorator_editor->update($this->field, ListField::NONE_VALUE, 'peggy-pink', false);
    }

    public function testItDeleteExistingNoneColorWhenFieldIsRequired(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('delete')->with(self::FIELD_ID, ListField::NONE_VALUE);
        $this->bound_decorator_editor->update(
            $this->field,
            ListField::NONE_VALUE,
            '#FFFFFF',
            true
        );
    }
}
