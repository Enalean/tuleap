<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\XML\Exporter\FieldChange;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class FieldChangeFileBuilderTest extends TestCase
{
    /**
     * @var FieldChangeFileBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new FieldChangeFileBuilder();
    }

    public function testItBuildsTheFieldChangeNode(): void
    {
        $changeset_node = new SimpleXMLElement('<changeset/>');

        $this->builder->build(
            $changeset_node,
            'field_file_01',
            [1456]
        );

        $this->assertTrue(isset($changeset_node->field_change));
        $field_change_node = $changeset_node->field_change;

        $this->assertSame("file", (string) $field_change_node['type']);
        $this->assertSame("field_file_01", (string) $field_change_node['field_name']);
        $this->assertSame("fileinfo_1456", (string) $field_change_node->value['ref']);
    }

    public function testItBuildsTheFieldChangeNodeWithValueAsNull(): void
    {
        $changeset_node = new SimpleXMLElement('<changeset/>');

        $this->builder->build(
            $changeset_node,
            'field_file_01',
            []
        );

        $this->assertTrue(isset($changeset_node->field_change));
        $field_change_node = $changeset_node->field_change;

        $this->assertSame("file", (string) $field_change_node['type']);
        $this->assertSame("field_file_01", (string) $field_change_node['field_name']);
        $this->assertSame("", (string) $field_change_node->value);
    }
}
