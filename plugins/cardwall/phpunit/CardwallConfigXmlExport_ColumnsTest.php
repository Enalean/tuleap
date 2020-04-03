<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class CardwallConfigXmlExport_ColumnsTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker1;
    /**
     * @var Cardwall_OnTop_Config|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $cardwall_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XML_RNGValidator
     */
    private $xml_validator;
    /** @var CardwallConfigXmlExport **/
    private $xml_exporter;

    /** @var SimpleXMLElement **/
    private $root;

    /** @var Cardwall_OnTop_ConfigFactory **/
    private $config_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(140);
        $this->tracker1 = Mockery::mock(Tracker::class);
        $this->tracker1->shouldReceive('getId')->andReturn(214);
        $this->root     = new SimpleXMLElement('<projects/>');

        $this->cardwall_config = \Mockery::spy(\Cardwall_OnTop_Config::class);
        $this->cardwall_config->shouldReceive('isEnabled')->andReturns(true);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $this->tracker_factory->shouldReceive('getTrackersByGroupId')->with(140)->andReturn([214 => $this->tracker1]);

        $this->config_factory = \Mockery::spy(\Cardwall_OnTop_ConfigFactory::class);
        $this->config_factory->shouldReceive('getOnTopConfig')->with($this->tracker1)->andReturns($this->cardwall_config);

        $this->xml_validator = \Mockery::spy(\XML_RNGValidator::class);

        $this->xml_exporter = new CardwallConfigXmlExport($this->project, $this->tracker_factory, $this->config_factory, $this->xml_validator);
    }

    public function testItDumpsNoColumnsWhenNoColumnsDefined(): void
    {
        $this->cardwall_config->shouldReceive('getDashboardColumns')->andReturns(new Cardwall_OnTop_Config_ColumnCollection(array()));
        $this->cardwall_config->shouldReceive('getMappings')->andReturns(array());

        $this->xml_exporter->export($this->root);
        $this->assertCount(0, $this->root->cardwall->trackers->tracker->children());
    }

    public function testItDumpsColumnsAsDefined(): void
    {
        $this->cardwall_config->shouldReceive('getDashboardColumns')->andReturns(new Cardwall_OnTop_Config_ColumnCollection(array(
            new Cardwall_Column(112, "Todo", "red"),
            new Cardwall_Column(113, "On going", "fiesta-red"),
            new Cardwall_Column(113, "On going", "rgb(255,255,255)")
        )));

        $this->cardwall_config->shouldReceive('getMappings')->andReturns(array());

        $this->xml_exporter->export($this->root);
        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;

        $this->assertCount(3, $column_xml);
    }

    public function testItDumpsColumnsAsDefinedWithMappings(): void
    {
        $this->cardwall_config->shouldReceive('getDashboardColumns')->andReturns(new Cardwall_OnTop_Config_ColumnCollection(array(
            new Cardwall_Column(112, "Todo", "red"),
            new Cardwall_Column(113, "On going", "fiesta-red"),
            new Cardwall_Column(113, "On going", "rgb(255,255,255)")
        )));

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getXMLId')->andReturn('T200');
        $field = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $field->shouldReceive('getXMLId')->andReturn('F201');

        $value_mapping = \Mockery::spy(\Cardwall_OnTop_Config_ValueMapping::class);
        $value_mapping->shouldReceive('getXMLValueId')->andReturns('V304');
        $value_mapping->shouldReceive('getColumnId')->andReturns(4);

        $mapping = \Mockery::spy(\Cardwall_OnTop_Config_TrackerMappingFreestyle::class);
        $mapping->shouldReceive('getTracker')->andReturns($tracker);
        $mapping->shouldReceive('getField')->andReturns($field);
        $mapping->shouldReceive('getValueMappings')->andReturns(array($value_mapping));
        $mapping->shouldReceive('isCustom')->andReturns(true);

        $this->cardwall_config->shouldReceive('getMappings')->andReturns(array($mapping));

        $this->xml_exporter->export($this->root);

        $column_xml = $this->root->cardwall->trackers->tracker->columns->column;
        $this->assertCount(3, $column_xml);

        $mapping_xml = $this->root->cardwall->trackers->tracker->mappings->mapping;
        $this->assertCount(1, $mapping_xml);
        $this->assertEquals('T200', $mapping_xml['tracker_id']);
        $this->assertEquals('F201', $mapping_xml['field_id']);

        $mapping_values_xml = $this->root->cardwall->trackers->tracker->mappings->mapping->values->value;
        $this->assertCount(1, $mapping_values_xml);
        $this->assertEquals('V304', $mapping_values_xml['value_id']);
        $this->assertEquals('C4', $mapping_values_xml['column_id']);
    }
}
