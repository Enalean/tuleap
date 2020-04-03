<?php
/**
 * Copyright (c) Enalean, 2015 - present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Semantic;

use BaseLanguage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Title;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Semantic_TitleTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Tracker_Semantic_Title
     */
    private $semantic_title;

    /**
     * @var SimpleXMLElement
     */
    private $root;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_Text
     */
    private $field;

    public function setUp(): void
    {
        $this->xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/../../_fixtures/ImportTrackerSemanticTitleTest.xml')
        );

        $this->tracker = Mockery::mock(Tracker::class);
        $this->field   = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $this->field->shouldReceive('getId')->andReturn(102);
        $this->semantic_title = new Tracker_Semantic_Title($this->tracker, $this->field);
        $this->root           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
    }

    public function testExport()
    {
        $GLOBALS['Language'] = Mockery::mock(BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->with(
            'plugin_tracker_admin_semantic',
            'title_label'
        )->andReturns('Title');
        $GLOBALS['Language']->shouldReceive('getText')->with(
            'plugin_tracker_admin_semantic',
            'title_description'
        )->andReturns('Define the title of an artifact');

        $array_mapping = ['F13' => '102'];
        $this->semantic_title->exportToXML($this->root, $array_mapping);

        $this->assertEquals((string) $this->xml->shortname, (string) $this->root->semantic->shortname);
        $this->assertEquals((string) $this->xml->label, (string) $this->root->semantic->label);
        $this->assertEquals((string) $this->xml->description, (string) $this->root->semantic->description);
        $this->assertEquals((string) $this->xml->field['REF'], (string) $this->root->semantic->field['REF']);
    }

    public function testItDoesntExportTheFieldIfNotDefinedInMapping()
    {
        $this->semantic_title->exportToXML($this->root, []);

        $this->assertEquals(0, count($this->root->children()));
    }
}
