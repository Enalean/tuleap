<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer\Table\Sort;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SortWithIntegrityCheckedTest extends TestCase
{
    private array $sort_with_used_field;
    private array $sort_columns_array;
    private array $sort_with_unused_field;

    protected function setUp(): void
    {
        $renderer_id                  = 10;
        $used_field_id                = 123;
        $used_field                   = new \Tuleap\Tracker\FormElement\Field\String\StringField($used_field_id, 456, 0, 'field', 'Correct field', '', 1, 'P', false, false, 1);
        $this->sort_with_used_field   = [
            'renderer_id ' => $renderer_id,
            'field_id' => $used_field_id,
            'is_desc' => true,
            'rank' => 1,
            'field' => $used_field,
        ];
        $unused_field                 = new \Tuleap\Tracker\FormElement\Field\String\StringField(234, 456, 0, 'field', 'Correct field', '', 0, 'P', false, false, 1);
        $this->sort_with_unused_field = [
            'renderer_id ' => $renderer_id,
            'field_id' => $unused_field->getId(),
            'is_desc' => true,
            'rank' => 2,
            'field' => $unused_field,
        ];
        $sort_without_field           = [
            'renderer_id ' => $renderer_id,
            'field_id' => $used_field_id,
            'is_desc' => true,
            'rank' => 3,

        ];
        $this->sort_columns_array = [$this->sort_with_used_field, $sort_without_field, $this->sort_with_unused_field];
    }

    public function testItBuildsACollectionOfValidUsedSortField(): void
    {
        $sort = SortWithIntegrityChecked::getSortOnUsedFields($this->sort_columns_array);
        self::assertSame([$this->sort_with_used_field], $sort);
    }

    public function testItBuildsACollectionOfValidSortField(): void
    {
        $sort = SortWithIntegrityChecked::getSort($this->sort_columns_array);
        self::assertSame([$this->sort_with_used_field, $this->sort_with_unused_field], $sort);
    }
}
