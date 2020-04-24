<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use Mockery;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Creation\JiraImporter\Import\ErrorCollector;

final class JiraToTuleapFieldTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \SimpleXMLElement
     */
    private $jira_atf_fieldset;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldXmlExporter
     */
    private $field_exporter;

    /**
     * @var JiraToTuleapFieldTypeMapper
     */
    private $mapper;

    /**
     * @var \SimpleXMLElement
     */
    private $jira_custom_fieldset;

    protected function setUp(): void
    {
        $this->field_exporter = Mockery::mock(FieldXmlExporter::class);
        $this->mapper         = new JiraToTuleapFieldTypeMapper(
            $this->field_exporter,
            new ErrorCollector()
        );

        $this->jira_atf_fieldset = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><formElement type="fieldset"/>'
        );

        $this->jira_custom_fieldset = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><formElement type="fieldset"/>'
        );
    }

    public function testJiraSummaryFieldIsMappedToStringField(): void
    {
        $jira_field = [
            "name" => "Summary",
            "id" => "summary",
            'schema' => [
                'type' => 'string',
                'system' => 'summary'
            ]
        ];
        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_atf_fieldset,
                Tracker_FormElement_Field_String::TYPE,
                $jira_field['id'],
                $jira_field['name'],
                $jira_field['id'],
                1,
                "1",
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            "1",
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraDescriptionFieldIsMappedToStringField(): void
    {
        $jira_field = [
            "name" => "Description",
            "id" => "description",
            'schema' => [
                'type' => 'string',
                'system' => 'description'
            ]
        ];

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_atf_fieldset,
                Tracker_FormElement_Field_Text::TYPE,
                $jira_field['id'],
                $jira_field['name'],
                $jira_field['id'],
                2,
                "1",
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            "1",
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }

    public function testJiraTextFieldFieldIsMappedToStringField(): void
    {
        $jira_field = [
            "name" => "String Field",
            "id" => "fieldid",
            'schema' => [
                'type' => 'string',
                'custom' => 'com.atlassian.jira.plugin.system.customfieldtypes:textfield',
                'customId' => 10045
            ]
        ];

        $collection = new FieldMappingCollection();

        $this->field_exporter->shouldReceive('exportField')->withArgs(
            [
                $this->jira_custom_fieldset,
                Tracker_FormElement_Field_String::TYPE,
                $jira_field['id'],
                $jira_field['name'],
                $jira_field['id'],
                1,
                "1",
                $collection
            ]
        );

        $this->mapper->exportFieldToXml(
            $jira_field,
            "1",
            $this->jira_atf_fieldset,
            $this->jira_custom_fieldset,
            $collection
        );
    }
}
