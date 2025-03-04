<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use SimpleXMLElement;
use Tracker_Semantic_Description;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Tracker_Semantic_DescriptionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Tracker_Semantic_Description
     */
    private $semantic;
    /**
     * @var SimpleXMLElement
     */
    private $root;

    protected function setUp(): void
    {
        $this->root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $tracker = TrackerTestBuilder::aTracker()->build();
        $field   = TextFieldBuilder::aTextField(104)->build();

        $this->semantic = new Tracker_Semantic_Description($tracker, $field);
    }

    public function testExport()
    {
        $array_xml_mapping = ['F14' => 104];

        $this->semantic->exportToXML($this->root, $array_xml_mapping);

        $this->assertTrue($this->root->count() > 0);
        $this->assertEquals('description', $this->root->semantic['type']);
        $this->assertEquals('description', $this->root->semantic->shortname);
    }

    public function testItDoesNotExportIfFieldIsNotExported()
    {
        $this->root        = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $this->semantic->exportToXML($this->root, $array_xml_mapping);

        $this->assertEquals(0, $this->root->count());
    }
}
