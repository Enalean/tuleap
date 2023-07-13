<?php
/**
 * Copyright (c) Enalean, 2022 — Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer\Table;

use Tracker_FormElement_Field;
use Tracker_Report_Renderer_Table;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class UsedFieldsRetrieverTest extends TestCase
{
    public function testItReturnsTheFieldsUsedInTableColumnsUserCanRead(): void
    {
        $retriever      = new UsedFieldsRetriever();
        $user           = UserTestBuilder::aUser()->build();
        $renderer_table = $this->createMock(Tracker_Report_Renderer_Table::class);

        $field_01 = $this->createMock(Tracker_FormElement_Field::class);
        $field_02 = $this->createMock(Tracker_FormElement_Field::class);
        $field_03 = $this->createMock(Tracker_FormElement_Field::class);

        $field_01->method('userCanRead')->with($user)->willReturn(true);
        $field_02->method('userCanRead')->with($user)->willReturn(true);
        $field_03->method('userCanRead')->with($user)->willReturn(false);

        $renderer_table
            ->method('getColumns')
            ->willReturn([
                [
                    'field' => $field_01,
                    'rank'  => 3,
                ],
                [
                    'field' => $field_02,
                    'rank'  => 1,
                ],
                [
                    'field' => $field_03,
                    'rank'  => 2,
                ],
            ]);

        $fields = $retriever->getUsedFieldsInRendererUserCanSee(
            $user,
            $renderer_table,
        );

        self::assertCount(2, $fields);
        self::assertSame(
            [$field_02, $field_01],
            $fields
        );
    }
}
