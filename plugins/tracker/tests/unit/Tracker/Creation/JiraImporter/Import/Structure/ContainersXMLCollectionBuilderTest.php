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

        $builder->buildCollectionOfFieldsetsXML($parent_node);
        $this->assertCount(2, $parent_node->children());

        $jira_atf_fieldset = $parent_node->formElement[0];
        $this->assertEquals("fieldset", $jira_atf_fieldset['type']);
        $this->assertEquals("F1", $jira_atf_fieldset['ID']);
        $this->assertEquals("0", $jira_atf_fieldset['rank']);
        $this->assertEquals("1", $jira_atf_fieldset['use_it']);

        $form_element_name = $jira_atf_fieldset->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("jira_atf", (string) $form_element_name);
        $form_element_label = $jira_atf_fieldset->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Jira ATF", (string) $form_element_label);

        $jira_custom_fieldset = $parent_node->formElement[1];
        $this->assertEquals("fieldset", $jira_custom_fieldset['type']);
        $this->assertEquals("F2", $jira_custom_fieldset['ID']);
        $this->assertEquals("0", $jira_custom_fieldset['rank']);
        $this->assertEquals("1", $jira_custom_fieldset['use_it']);

        $form_element_name = $jira_custom_fieldset->name;
        $this->assertNotNull($form_element_name);
        $this->assertEquals("jira_custom", (string) $form_element_name);
        $form_element_label = $jira_custom_fieldset->label;
        $this->assertNotNull($form_element_label);
        $this->assertEquals("Jira Custom Fields", (string) $form_element_label);
    }
}
