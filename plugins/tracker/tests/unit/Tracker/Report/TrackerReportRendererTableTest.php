<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SimpleXMLElement;
use Tracker_FormElement_Field_ArtifactId;
use Tracker_Report_Renderer_Table;

final class TrackerReportRendererTableTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|Tracker_Report_Renderer_Table
     */
    private $tracker_report_renderer_table;

    /**
     * @var array
     */
    private $matchings_ids;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_ArtifactId
     */
    private $form_elements_1;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_ArtifactId
     */
    private $form_elements_2;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_ArtifactId
     */
    private $form_elements_3;

    /**
     * @var array
     */
    private $columns;
    /**
     * @var SimpleXMLElement
     */
    private $xml;

    public function setUp(): void
    {
        parent::setUp();
        $this->tracker_report_renderer_table = \Mockery::mock(Tracker_Report_Renderer_Table::class)->makePartial();


        $this->matchings_ids = [
            "last_changeset_id" => "98,99,100",
        ];

        $this->form_elements_1 = Mockery::mock(Tracker_FormElement_Field_ArtifactId::class);
        $this->form_elements_2 = Mockery::mock(Tracker_FormElement_Field_ArtifactId::class);
        $this->form_elements_3 = Mockery::mock(Tracker_FormElement_Field_ArtifactId::class);

        $this->form_elements_1->shouldReceive('getId')->andReturn(101);
        $this->form_elements_2->shouldReceive('getId')->andReturn(102);
        $this->form_elements_3->shouldReceive('getId')->andReturn(103);

        $this->form_elements_1->shouldReceive('isUsed')->andReturn(true);
        $this->form_elements_2->shouldReceive('isUsed')->andReturn(true);
        $this->form_elements_3->shouldReceive('isUsed')->andReturn(true);

        $this->form_elements_1->shouldReceive('isMultiple')->andReturn(false);
        $this->form_elements_2->shouldReceive('isMultiple')->andReturn(false);
        $this->form_elements_3->shouldReceive('isMultiple')->andReturn(false);

        $this->form_elements_1->shouldReceive('getQuerySelect')->andReturn("a.id AS `artifact_id`");
        $this->form_elements_1->shouldReceive('getQueryFrom')->andReturn("");

        $this->form_elements_2->shouldReceive('getQuerySelect')->andReturn("a.id AS `artifact_id`");
        $this->form_elements_2->shouldReceive('getQueryFrom')->andReturn("");

        $this->form_elements_3->shouldReceive('getQuerySelect')->andReturn("a.id AS `artifact_id`");
        $this->form_elements_3->shouldReceive('getQueryFrom')->andReturn("");

        $this->form_elements_1->shouldReceive('getQueryOrderby')->andReturn("artifact_id");

        $this->columns = [
            "101" => [
                'field' => $this->form_elements_1,
                'field_id' => "101",
            ],
            "102" => [
                'field' => $this->form_elements_2,
                'field_id' => "102",
            ],
            "103" => [
                'field' => $this->form_elements_3,
                'field_id' => "103",
            ],
        ];

        $this->tracker_report_renderer_table->shouldReceive('sortHasUsedField')->andReturn(true);

        $this->xml = new SimpleXMLElement('<field/>');
    }

    public function testOrderNotDefinedWhenNoSortDefined(): void
    {
        $this->tracker_report_renderer_table->shouldReceive('getSort')->andReturn([]);

        $this->assertSame(
            [' SELECT a.id AS id, c.id AS changeset_id , a.id AS `artifact_id`, a.id AS `artifact_id`, a.id AS `artifact_id` FROM tracker_artifact AS a INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id)    WHERE c.id IN (98,99,100) '],
            $this->tracker_report_renderer_table->buildOrderedQuery($this->matchings_ids, $this->columns)
        );
    }

    public function testItAddOnlyNatureInReportXmlExport()
    {
        $field_info = [
            'field_id'       => 10,
            'artlink_nature' => '_is_child',
        ];

        $mapping = $this->mapFieldWithNature(10, '_is_child', null);

        $this->tracker_report_renderer_table->shouldReceive('getColumns')->andReturn($mapping['field']);
        $this->tracker_report_renderer_table->shouldReceive('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('_is_child', (string) $this->xml->columns->field['artlink-nature']);
    }

    public function testItAddOnlyFormatInReportXmlExport()
    {
        $field_info = [
            'field_id'              => 11,
            'artlink_nature_format' => '#%id',
        ];

        $mapping = $this->mapFieldWithNature(11, null, '#%id');

        $this->tracker_report_renderer_table->shouldReceive('getColumns')->andReturn($mapping['field']);
        $this->tracker_report_renderer_table->shouldReceive('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('#%id', (string) $this->xml->columns->field['artlink-nature-format']);
    }

    public function testItAddBothNatureAndFormatInTrackerReports()
    {
        $field_info = [
            'field_id'              => 12,
            'artlink_nature' => '_is_child',
            'artlink_nature_format' => '#%id',
        ];

        $mapping = $this->mapFieldWithNature(12, '_is_child', '#%id');

        $this->tracker_report_renderer_table->shouldReceive('getColumns')->andReturn($mapping['field']);
        $this->tracker_report_renderer_table->shouldReceive('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('_is_child', (string) $this->xml->columns->field['artlink-nature']);
        $this->assertEquals('#%id', (string) $this->xml->columns->field['artlink-nature-format']);
    }

    public function testItNeverAddNatureInTrackerReportsWithoutNature()
    {
        $field_info = [
            'field_id' => 13,
        ];

        $mapping = $this->mapFieldWithNature(13, null, null);

        $this->tracker_report_renderer_table->shouldReceive('getColumns')->andReturn($mapping['field']);
        $this->tracker_report_renderer_table->shouldReceive('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals(null, (string) $this->xml->columns);
    }

    private function mapFieldWithNature($id, $nature, $format)
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_String::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getId')->andReturn($id);
        $xml_mapping['F' . $field->getId()] = $field->getId();

        $field_mapping = [
            'field'                 => $field,
            'field_id'              => $id,
            'width'                 => '15',
            'rank'                  => '1',
        ];

        if ($nature) {
            $field_mapping['artlink_nature'] = "$nature";
        }

        if ($format) {
            $field_mapping['artlink_nature_format'] = "$format";
        }

        return [
            "field" => [$field_mapping],
            "xml"   => $xml_mapping,
        ];
    }
}
