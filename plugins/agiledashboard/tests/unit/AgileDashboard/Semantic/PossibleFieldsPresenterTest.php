<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PossibleFieldsPresenterTest extends TestCase
{
    public function testItBuildsPresenterFromNumericFieldList(): void
    {
        $field          = new \Tuleap\Tracker\FormElement\Field\Integer\IntegerField(
            1,
            10,
            0,
            'my_field',
            'My field',
            '',
            true,
            'P',
            false,
            false,
            1
        );
        $selected_field = new \Tuleap\Tracker\FormElement\Field\Integer\IntegerField(
            2,
            10,
            0,
            'my_selected_field',
            'My selected field',
            '',
            true,
            'P',
            false,
            false,
            1
        );

        $initial_effort_semantic = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $initial_effort_semantic->method('getFieldId')->willReturn(2);

        $presenter = PossibleFieldsPresenter::buildFromTrackerFieldList([$field, $selected_field], $initial_effort_semantic);
        self::assertSame($field->getId(), $presenter[0]->id);
        self::assertSame($field->getLabel(), $presenter[0]->label);
        self::assertFalse($presenter[0]->is_selected);

        self::assertSame($selected_field->getId(), $presenter[1]->id);
        self::assertSame($selected_field->getLabel(), $presenter[1]->label);
        self::assertTrue($presenter[1]->is_selected);
    }
}
