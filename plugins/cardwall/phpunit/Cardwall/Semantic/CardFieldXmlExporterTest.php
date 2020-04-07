<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use Cardwall_Semantic_CardFields;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List;

final class CardFieldXmlExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    /**
     * @var CardFieldXmlExporter
     */
    private $exporter;

    /**
     * @var BackgroundColorDao
     */
    private $color_dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->color_dao = Mockery::spy(BackgroundColorDao::class);
        $this->exporter  = new CardFieldXmlExporter($this->color_dao);

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';

        $this->xml_tree = new SimpleXMLElement($data);

        $this->tracker = Mockery::spy(Tracker::class);
    }

    public function testItShouldExportCardFields()
    {
        $mapping = [
            'F102' => 13,
            'F103' => 14
        ];

        $severity_field = Mockery::spy(Tracker_FormElement_Field_List::class);
        $severity_field->shouldReceive('getId')->andReturn(13);

        $status_field = Mockery::spy(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn(14);

        $fields = [
            $severity_field,
            $status_field
        ];

        $semantic = Mockery::spy(Cardwall_Semantic_CardFields::class);
        $semantic->shouldReceive('getFields')->andReturn($fields);
        $semantic->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->exporter->exportToXml($this->xml_tree, $mapping, $semantic);

        $semantic = $this->xml_tree->semantic->attributes();
        $this->assertEquals($semantic->type, Cardwall_Semantic_CardFields::NAME);

        $fields = $this->xml_tree->semantic->field;
        $this->assertEquals('F102', $fields[0]->attributes());
        $this->assertEquals('F103', $fields[1]->attributes());
    }

    public function testItShouldExportBackgroundColor()
    {
        $this->color_dao->shouldReceive('searchBackgroundColor')->andReturn(13);

        $mapping = [
            'F102' => 13,
            'F103' => 14
        ];

        $semantic = Mockery::spy(Cardwall_Semantic_CardFields::class);
        $semantic->shouldReceive('getFields')->andReturn([]);
        $semantic->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->exporter->exportToXml($this->xml_tree, $mapping, $semantic);

        $semantic = $this->xml_tree->semantic->attributes();
        $this->assertEquals($semantic->type, Cardwall_Semantic_CardFields::NAME);

        $background_color_field = $this->xml_tree->semantic->{ 'background-color' };
        $this->assertEquals('F102', $background_color_field[0]->attributes());
    }
}
