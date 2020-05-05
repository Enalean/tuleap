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

namespace Tuleap\unit\Tracker\XML\Exporter\FieldChange;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use XML_SimpleXMLCDATAFactory;

class FieldChangeTextBuilderTest extends TestCase
{
    /**
     * @var FieldChangeTextBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new FieldChangeTextBuilder(
            new XML_SimpleXMLCDATAFactory()
        );
    }

    public function testItBuildsTheFieldChangeNode(): void
    {
        $changeset_node = new SimpleXMLElement('<changeset/>');

        $this->builder->build(
            $changeset_node,
            'field_text_01',
            'my text content',
            'html'
        );

        $this->assertTrue(isset($changeset_node->field_change));
        $field_change_node = $changeset_node->field_change;

        $this->assertSame("text", (string) $field_change_node['type']);
        $this->assertSame("field_text_01", (string) $field_change_node['field_name']);
        $this->assertSame("my text content", (string) $field_change_node->value);
        $this->assertSame("html", (string) $field_change_node->value['format']);
    }
}
