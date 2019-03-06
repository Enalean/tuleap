<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\Renderer;

use TuleapTestCase;
use SimpleXMLElement;
use Tracker_Report_Renderer_Table;

require_once 'bootstrap.php';

class TrackerReportRendererTableTest extends TuleapTestCase
{

    private $report;
    private $xml;

    public function setUp()
    {
        parent::setUp();

        $this->report = partial_mock(
            'Tracker_Report_Renderer_Table',
            array('getColumns', 'getFieldWhenUsingNatures', 'getField', 'getSort'),
            array( '1', 'report', 'name', 'description', 'rank', 'chunksz', 'multisort')
        );

        $this->xml = new SimpleXMLElement('<field/>');
    }

    public function itAddOnlyNatureInReportXmlExport()
    {
        $field_info = array(
            'field_id'       => 10,
            'artlink_nature' => '_is_child'
        );

        $mapping = $this->mapFieldWithNature(10, '_is_child', null);

        stub($this->report)->getColumns()->returns($mapping['field']);
        $this->report->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEqual((string)$this->xml->columns->field['artlink-nature'], '_is_child');
    }

    public function itAddOnlyFormatInReportXmlExport()
    {
        $field_info = array(
            'field_id'              => 11,
            'artlink_nature_format' => '#%id'
        );

        $mapping = $this->mapFieldWithNature(11, null, '#%id');

        stub($this->report)->getColumns()->returns($mapping['field']);
        $this->report->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEqual((string)$this->xml->columns->field['artlink-nature-format'], '#%id');
    }

    public function itAddBothNatureAndFormatInTrackerReports()
    {
        $field_info = array(
            'field_id'              => 12,
            'artlink_nature' => '_is_child',
            'artlink_nature_format' => '#%id'
        );

        $mapping = $this->mapFieldWithNature(12, '_is_child', '#%id');

        stub($this->report)->getColumns()->returns($mapping['field']);
        $this->report->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEqual((string)$this->xml->columns->field['artlink-nature'], '_is_child');
        $this->assertEqual((string)$this->xml->columns->field['artlink-nature-format'], '#%id');
    }

    public function itNeverAddNatureInTrackerReportsWithoutNature()
    {
        $field_info = array(
            'field_id' => 13
        );

        $mapping = $this->mapFieldWithNature(13, null, null);

        stub($this->report)->getColumns()->returns($mapping['field']);
        $this->report->exportToXml($this->xml, $field_info, $mapping['xml']);

        $this->assertEqual((string)$this->xml->columns, null);
    }

    private function mapFieldWithNature($id, $nature, $format)
    {
        $field = partial_mock(
            'Tracker_FormElement_Field_String',
            array('getId')
        );
        stub($field)->getId()->returns($id);
        $xml_mapping['F'. $field->getId()] = $field->getId();

        $field_mapping = array(
            'field'                 => $field,
            'field_id'              => $id,
            'width'                 => '15',
            'rank'                  => '1'
        );

        if ($nature) {
            $field_mapping['artlink_nature'] = "$nature";
        }

        if ($format) {
            $field_mapping['artlink_nature_format'] = "$format";
        }

        return array(
            "field" => array($field_mapping),
            "xml"   => $xml_mapping
        );
    }
}
