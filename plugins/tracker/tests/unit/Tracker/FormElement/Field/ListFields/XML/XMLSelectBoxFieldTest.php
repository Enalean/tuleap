<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindUsersValue;
use Tuleap\Tracker\XML\IDGenerator;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

class XMLSelectBoxFieldTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItMustHaveABindTypeAtExportType(): void
    {
        $this->expectException(\LogicException::class);

        (new XMLSelectBoxField('some_id', 'status'))
            ->export(new \SimpleXMLElement('<formElements />'));
    }

    public function testItHasDefaultAttributes(): void
    {
        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withBindStatic()
            ->export(new \SimpleXMLElement('<formElements />'));

        assertEquals('some_id', $xml['ID']);
        assertEquals('sb', $xml['type']);

        assertEquals('static', $xml->bind['type']);
        assertEquals('0', $xml->bind['is_rank_alpha']);
    }

    public function testValuesWillBeRankedAlphanumerically(): void
    {
        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withBindStatic()
            ->withAlphanumericRank()
            ->export(new \SimpleXMLElement('<formElements />'));

        assertEquals('1', $xml->bind['is_rank_alpha']);
    }

    public function testBindTypeIsStaticBecauseItsBoundToStaticValues(): void
    {
        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withStaticValues(
                new XMLBindStaticValue('V1', 'Todo'),
            )
            ->export(new \SimpleXMLElement('<formElements />'));

        assertEquals('static', $xml->bind['type']);
    }

    public function testFieldBoundToStaticValuesCannotHaveUsersValues(): void
    {
        $this->expectException(\LogicException::class);

        (new XMLSelectBoxField('some_id', 'status'))
            ->withStaticValues(
                new XMLBindStaticValue('V1', 'Todo'),
            )
            ->withUsersValues(
                new XMLBindUsersValue('project_members')
            )
            ->export(new \SimpleXMLElement('<formElements />'));
    }

    public function testFieldBoundToUsersValuesCannotHaveStaticValues(): void
    {
        $this->expectException(\LogicException::class);

        (new XMLSelectBoxField('some_id', 'status'))
            ->withUsersValues(
                new XMLBindUsersValue('project_members')
            )
            ->withStaticValues(
                new XMLBindStaticValue('V1', 'Todo'),
            )
            ->export(new \SimpleXMLElement('<formElements />'));
    }

    public function testFieldBoundToUsersValues(): void
    {
        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withUsersValues(
                new XMLBindUsersValue('project_members'),
            )
            ->export(new \SimpleXMLElement('<formElements />'));

        assertEquals(\Tracker_FormElement_Field_List_Bind_Users::TYPE, $xml->bind['type']);
        assertCount(1, $xml->bind->items->item);
        assertEquals('project_members', $xml->bind->items->item[0]['label']);
    }

    public function testItWithFixedIdForIds(): void
    {
        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withStaticValues(
                new XMLBindStaticValue('V1', 'Todo'),
                new XMLBindStaticValue('V2', 'In progress'),
            )
            ->export(new \SimpleXMLElement('<formElements />'));

        assertCount(2, $xml->bind->items->item);
        assertEquals('V1', $xml->bind->items->item[0]['ID']);
        assertEquals('V2', $xml->bind->items->item[1]['ID']);
    }

    public function testItWithGeneratedIds(): void
    {
        $id_generator = new class implements IDGenerator
        {
            public function getNextId(): int
            {
                return 58;
            }
        };

        $xml = (new XMLSelectBoxField('some_id', 'status'))
            ->withStaticValues(
                new XMLBindStaticValue($id_generator, 'Todo'),
            )
            ->export(new \SimpleXMLElement('<formElements />'));

        assertCount(1, $xml->bind->items->item);

        assertEquals('V58', $xml->bind->items->item[0]['ID']);
    }
}
