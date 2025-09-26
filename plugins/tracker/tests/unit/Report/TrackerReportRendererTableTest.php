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

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tracker_Report_Renderer_Table;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerReportRendererTableTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_Report_Renderer_Table&MockObject $tracker_report_renderer_table;

    private array $matchings_ids;

    private ArtifactIdField&MockObject $form_elements_1;

    private ArtifactIdField&MockObject $form_elements_2;

    private ArtifactIdField&MockObject $form_elements_3;

    private array $columns;

    private SimpleXMLElement $xml;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->tracker_report_renderer_table = $this->createPartialMock(Tracker_Report_Renderer_Table::class, [
            'getSort',
            'getColumns',
            'sortHasUsedField',
        ]);


        $this->matchings_ids = [
            'last_changeset_id' => '98,99,100',
        ];

        $this->form_elements_1 = $this->createMock(ArtifactIdField::class);
        $this->form_elements_2 = $this->createMock(ArtifactIdField::class);
        $this->form_elements_3 = $this->createMock(ArtifactIdField::class);

        $this->form_elements_1->method('getId')->willReturn(101);
        $this->form_elements_2->method('getId')->willReturn(102);
        $this->form_elements_3->method('getId')->willReturn(103);

        $this->form_elements_1->method('isUsed')->willReturn(true);
        $this->form_elements_2->method('isUsed')->willReturn(true);
        $this->form_elements_3->method('isUsed')->willReturn(true);

        $this->form_elements_1->method('isMultiple')->willReturn(false);
        $this->form_elements_2->method('isMultiple')->willReturn(false);
        $this->form_elements_3->method('isMultiple')->willReturn(false);

        $this->form_elements_1->method('getQuerySelect')->willReturn('a.id AS `artifact_id`');
        $this->form_elements_1->method('getQueryFrom')->willReturn('');

        $this->form_elements_2->method('getQuerySelect')->willReturn('a.id AS `artifact_id`');
        $this->form_elements_2->method('getQueryFrom')->willReturn('');

        $this->form_elements_3->method('getQuerySelect')->willReturn('a.id AS `artifact_id`');
        $this->form_elements_3->method('getQueryFrom')->willReturn('');

        $this->form_elements_1->method('getQueryOrderby')->willReturn('artifact_id');

        $this->columns = [
            '101' => [
                'field' => $this->form_elements_1,
                'field_id' => '101',
            ],
            '102' => [
                'field' => $this->form_elements_2,
                'field_id' => '102',
            ],
            '103' => [
                'field' => $this->form_elements_3,
                'field_id' => '103',
            ],
        ];

        $this->tracker_report_renderer_table->method('sortHasUsedField')->willReturn(true);

        $this->xml = new SimpleXMLElement('<field/>');
    }

    public function testOrderNotDefinedWhenNoSortDefined(): void
    {
        $this->tracker_report_renderer_table->method('getSort')->willReturn([]);

        self::assertSame(
            [' SELECT a.id AS id, c.id AS changeset_id , a.id AS `artifact_id`, a.id AS `artifact_id`, a.id AS `artifact_id` FROM tracker_artifact AS a INNER JOIN tracker_changeset AS c ON (c.artifact_id = a.id)    WHERE c.id IN (98,99,100) '],
            $this->tracker_report_renderer_table->buildOrderedQuery($this->matchings_ids, $this->columns)
        );
    }

    public function testItAddOnlyNatureInReportXmlExport(): void
    {
        $field_info = [
            'field_id'       => 10,
            'artlink_nature' => '_is_child',
        ];

        $mapping = $this->mapFieldWithNature(10, '_is_child', null);

        $this->tracker_report_renderer_table->method('getColumns')->willReturn($mapping['field']);
        $this->tracker_report_renderer_table->method('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('_is_child', (string) $this->xml->columns->field['artlink-nature']);
    }

    public function testItAddOnlyFormatInReportXmlExport(): void
    {
        $field_info = [
            'field_id'              => 11,
            'artlink_nature_format' => '#%id',
        ];

        $mapping = $this->mapFieldWithNature(11, null, '#%id');

        $this->tracker_report_renderer_table->method('getColumns')->willReturn($mapping['field']);
        $this->tracker_report_renderer_table->method('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('#%id', (string) $this->xml->columns->field['artlink-nature-format']);
    }

    public function testItAddBothNatureAndFormatInTrackerReports(): void
    {
        $field_info = [
            'field_id'              => 12,
            'artlink_nature' => '_is_child',
            'artlink_nature_format' => '#%id',
        ];

        $mapping = $this->mapFieldWithNature(12, '_is_child', '#%id');

        $this->tracker_report_renderer_table->method('getColumns')->willReturn($mapping['field']);
        $this->tracker_report_renderer_table->method('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals('_is_child', (string) $this->xml->columns->field['artlink-nature']);
        $this->assertEquals('#%id', (string) $this->xml->columns->field['artlink-nature-format']);
    }

    public function testItNeverAddNatureInTrackerReportsWithoutNature(): void
    {
        $field_info = [
            'field_id' => 13,
        ];

        $mapping = $this->mapFieldWithNature(13, null, null);

        $this->tracker_report_renderer_table->method('getColumns')->willReturn($mapping['field']);
        $this->tracker_report_renderer_table->method('getSort');
        $this->tracker_report_renderer_table->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEquals(null, (string) $this->xml->columns);
    }

    private function mapFieldWithNature(int $id, ?string $nature, ?string $format): array
    {
        $field                              = StringFieldBuilder::aStringField($id)->build();
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
            'field' => [$field_mapping],
            'xml'   => $xml_mapping,
        ];
    }
}
