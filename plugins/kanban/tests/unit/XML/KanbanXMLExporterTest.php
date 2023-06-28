<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Kanban\XML;

use AgileDashboard_ConfigurationDao;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanFactory;
use Mockery;

final class KanbanXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var KanbanXMLExporter
     */
    private $kanban_export;
    /**
     * @var KanbanFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $kanban_factory;
    private AgileDashboard_ConfigurationDao & \PHPUnit\Framework\MockObject\MockObject $configuration_dao;

    protected function setUp(): void
    {
        $this->configuration_dao = $this->createMock(AgileDashboard_ConfigurationDao::class);
        $this->kanban_factory    = Mockery::mock(KanbanFactory::class);

        $this->kanban_export = new KanbanXMLExporter($this->configuration_dao, $this->kanban_factory);
    }

    public function testItExportsNothingIfNoKanban(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive(('getID'))->andReturn(10);

        $this->configuration_dao->method('isKanbanActivated')->willReturn(false);

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <kanban_list />';
        $xml_element = new \SimpleXMLElement($xml_data);
        $this->kanban_export->export($xml_element, $project);

        $this->assertEquals(new \SimpleXMLElement($xml_data), $xml_element);
    }

    public function testItExportsKanban(): void
    {
        $this->configuration_dao->method('isKanbanActivated')->willReturn(true);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive(('getID'))->andReturn(10);

        $kanban1 = new Kanban(10, 1, 'Alice task');
        $kanban2 = new Kanban(20, 2, 'Bob task');

        $this->kanban_factory->shouldReceive('getKanbanTrackerIds')->withArgs([$project->getID()])->andReturn([1, 2]);

        $this->kanban_factory->shouldReceive('getKanbanByTrackerId')->withArgs([1])->andReturn($kanban1);
        $this->kanban_factory->shouldReceive('getKanbanByTrackerId')->withArgs([2])->andReturn($kanban2);

        $xml_data    = '<?xml version="1.0" encoding="UTF-8"?>
                 <kanban_list />';
        $xml_element = new \SimpleXMLElement($xml_data);
        $this->kanban_export->export($xml_element, $project);

        $kanban_list_node = KanbanXMLExporter::NODE_KANBAN_LST;

        $kanban1_attributes = $xml_element->$kanban_list_node->kanban[0]->attributes();
        $this->assertEquals('T1', (string) $kanban1_attributes->tracker_id);
        $this->assertEquals('Alice task', (string) $kanban1_attributes->name);
        $this->assertEquals('K10', (string) $kanban1_attributes->ID);

        $kanban2_attributes = $xml_element->$kanban_list_node->kanban[1]->attributes();
        $this->assertEquals('T2', (string) $kanban2_attributes->tracker_id);
        $this->assertEquals('Bob task', (string) $kanban2_attributes->name);
        $this->assertEquals('K20', (string) $kanban2_attributes->ID);
    }
}
