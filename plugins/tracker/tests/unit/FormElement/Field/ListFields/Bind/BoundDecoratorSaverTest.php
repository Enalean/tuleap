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
final class BoundDecoratorSaverTest extends TestCase
{
    private const FIELD_ID = 101;

    private ListField $field;
    private BoundDecoratorSaver $bound_decorator_saver;
    private BindDecoratorDao&MockObject $bind_decorator_dao;

    protected function setUp(): void
    {
        $this->bind_decorator_dao    = $this->createMock(BindDecoratorDao::class);
        $this->bound_decorator_saver = new BoundDecoratorSaver($this->bind_decorator_dao);

        $this->field = SelectboxFieldBuilder::aSelectboxField(self::FIELD_ID)->build();
    }

    public function testItHasSpecificSaveForLegacyColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('save')->with(1024, 255, 255, 255);
        $this->bound_decorator_saver->save($this->field, 1024, '#FFFFFF');
    }

    public function testItHasSpecificSaveForNoneLegacyColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('saveNoneLegacyColor')->with(self::FIELD_ID, 255, 255, 255);
        $this->bound_decorator_saver->save($this->field, ListField::NONE_VALUE, '#FFFFFF');
    }

    public function testItHasSpecificSaveForTlpColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('saveTlpColor')->with(1024, 'peggy-pink');
        $this->bound_decorator_saver->save($this->field, 1024, 'peggy-pink');
    }

    public function testItHasSpecificSaveForNoneTlpColor(): void
    {
        $this->bind_decorator_dao->expects($this->once())->method('saveNoneTlpColor')->with(self::FIELD_ID, 'peggy-pink');
        $this->bound_decorator_saver->save($this->field, ListField::NONE_VALUE, 'peggy-pink');
    }
}
