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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use XML_SimpleXMLCDATAFactory;

final class FieldXmlExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldXmlExporter
     */
    private $exporter;

    protected function setUp(): void
    {
        $this->exporter = new FieldXmlExporter(
            new XML_SimpleXMLCDATAFactory(),
            new FieldNameFormatter()
        );
    }

    public function testItExportAField(): void
    {
        $parent_node = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><fieldset><formElements/></fieldset>');
        $collection = new FieldMappingCollection();

        $this->exporter->exportField(
            $parent_node,
            Tracker_FormElementFactory::FIELD_STRING_TYPE,
            "name",
            "Label",
            "Summary",
            1,
            true,
            [],
            [],
            $collection,
            null
        );

        $form_element_node = $parent_node->formElements->formElement;
        $this->assertNotNull($form_element_node);

        $this->assertEquals("string", $form_element_node['type']);
        $this->assertEquals("FSummary", $form_element_node['ID']);
        $this->assertEquals("1", $form_element_node['rank']);
        $this->assertEquals("1", $form_element_node['use_it']);

        $form_element_name = $form_element_node->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("name", (string) $form_element_name);
        $form_element_label = $form_element_node->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Label", (string) $form_element_label);
    }
}
