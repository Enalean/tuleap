<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use PHPUnit\Framework\TestCase;
use XML_SimpleXMLCDATAFactory;

class ContainersXMLCollectionBuilderTest extends TestCase
{
    public function testItExportsAFieldset(): void
    {
        $builder = new ContainersXMLCollectionBuilder(
            new XML_SimpleXMLCDATAFactory()
        );

        $parent_node = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><formElements/>');

        $builder->buildCollectionOfJiraContainersXML($parent_node);
        $this->assertCount(3, $parent_node->children());

        $details_fieldset = $parent_node->formElement[0];
        $this->assertEquals("fieldset", $details_fieldset['type']);
        $this->assertEquals("F1", $details_fieldset['ID']);
        $this->assertEquals("1", $details_fieldset['rank']);
        $this->assertEquals("1", $details_fieldset['use_it']);

        $form_element_name = $details_fieldset->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("details_fieldset", (string) $form_element_name);
        $form_element_label = $details_fieldset->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Details", (string) $form_element_label);

        $custom_fieldset = $parent_node->formElement[1];
        $this->assertEquals("fieldset", $custom_fieldset['type']);
        $this->assertEquals("F2", $custom_fieldset['ID']);
        $this->assertEquals("2", $custom_fieldset['rank']);
        $this->assertEquals("1", $custom_fieldset['use_it']);

        $form_element_name = $custom_fieldset->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("custom_fieldset", (string) $form_element_name);
        $form_element_label = $custom_fieldset->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Custom Fields", (string) $form_element_label);

        $left_column   = $details_fieldset->formElements->formElement[0];
        $this->assertEquals("column", $left_column['type']);
        $this->assertEquals("Fcol1", $left_column['ID']);
        $this->assertEquals("1", $left_column['rank']);

        $right_column  = $details_fieldset->formElements->formElement[1];
        $this->assertEquals("column", $right_column['type']);
        $this->assertEquals("Fcol2", $right_column['ID']);
        $this->assertEquals("2", $right_column['rank']);

        $attachment_fieldset = $parent_node->formElement[2];
        $this->assertEquals("fieldset", $attachment_fieldset['type']);
        $this->assertEquals("F3", $attachment_fieldset['ID']);
        $this->assertEquals("3", $attachment_fieldset['rank']);
        $this->assertEquals("1", $attachment_fieldset['use_it']);

        $form_element_name = $attachment_fieldset->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("attachment_fieldset", (string) $form_element_name);
        $form_element_label = $attachment_fieldset->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Attachments", (string) $form_element_label);
    }
}
