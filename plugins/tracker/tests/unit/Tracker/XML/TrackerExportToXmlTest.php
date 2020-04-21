<?php
/**
 * Copyright (c) Enalean SAS, 2011 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;
use Tracker;
use Tracker_CannedResponseManager;
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tracker_RulesManager;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Webhook\WebhookXMLExporter;
use UserManager;

class TrackerExportToXmlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\Mock|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\WorkflowFactory
     */
    private $workflow_factory;
    /**
     * @var Tracker_Hierarchy
     */
    private $hierarchy;

    /**
     * @var Mockery\MockInterface|UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever;

    protected function setUp(): void
    {
        $this->tracker = Mockery::mock(Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->shouldReceive('getId')->andReturn(110);
        $this->tracker->shouldReceive('getColor')->andReturn(TrackerColor::default());
        $this->tracker->shouldReceive('getUserManager')->andReturn(Mockery::mock(UserManager::class));
        $this->tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));

        $this->ugroup_retriever = Mockery::mock(UGroupRetrieverWithLegacy::class);
        $this->tracker->shouldReceive('getUGroupRetrieverWithLegacy')->andReturn($this->ugroup_retriever);

        $this->formelement_factory = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->tracker->shouldReceive('getFormElementFactory')->andReturn($this->formelement_factory);

        $rules_manager = Mockery::mock(Tracker_RulesManager::class);
        $rules_manager->shouldReceive('exportToXml');

        $this->workflow_factory = Mockery::mock(\WorkflowFactory::class);
        $this->workflow_factory->shouldReceive('getGlobalRulesManager')->andReturn(
            $rules_manager
        );

        $this->workflow_factory->shouldReceive('getWorkflowByTrackerId');

        $this->tracker->shouldReceive('getWorkflowFactory')->andReturn($this->workflow_factory);

        $this->hierarchy   = new Tracker_Hierarchy();
        $hierarchy_factory = Mockery::mock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getHierarchy')->andReturn($this->hierarchy);

        $this->tracker->shouldReceive('getHierarchyFactory')->andReturn($hierarchy_factory);

        $tracker_canned_response_manager = Mockery::mock(Tracker_CannedResponseManager::class);
        $this->tracker->shouldReceive('getCannedResponseManager')->andReturn($tracker_canned_response_manager);

        $canned_response_factory = Mockery::mock(\Tracker_CannedResponseFactory::class);
        $this->tracker->shouldReceive('getCannedResponseFactory')->andReturn($canned_response_factory);
        $canned_response_factory->shouldReceive('getCannedResponses');

        $tsm = Mockery::mock(\Tracker_SemanticManager::class);
        $this->tracker->shouldReceive('getTrackerSemanticManager')->andReturn($tsm);
        $tsm->shouldReceive('exportToXml');

        $report_factory = Mockery::mock(\Tracker_ReportFactory::class);
        $this->tracker->shouldReceive('getReportFactory')->andReturn($report_factory);
        $report_factory->shouldReceive('getReportsByTrackerId');

        $webhook_xml_exporter = Mockery::mock(WebhookXMLExporter::class);
        $webhook_xml_exporter->shouldReceive('exportTrackerWebhooksInXML')->once();
        $this->tracker->shouldReceive('getWebhookXMLExporter')->andReturn($webhook_xml_exporter);
    }

    public function testPermissionsExport()
    {
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn(
            [
                1   => ['PERM_1'],
                3   => ['PERM_2'],
                5   => ['PERM_3'],
                115 => ['PERM_3'],
            ]
        );
        $ugroups = [
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        ];

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn($ugroups);

        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $this->assertTrue(isset($xml->permissions));
        $this->assertEquals('tracker', (string) $xml->permissions->permission[0]['scope']);
        $this->assertEquals('UGROUP_1', (string) $xml->permissions->permission[0]['ugroup']);
        $this->assertEquals('PERM_1', (string) $xml->permissions->permission[0]['type']);

        $this->assertEquals('tracker', (string) $xml->permissions->permission[1]['scope']);
        $this->assertEquals('UGROUP_3', (string) $xml->permissions->permission[1]['ugroup']);
        $this->assertEquals('PERM_2', (string) $xml->permissions->permission[1]['type']);

        $this->assertEquals('tracker', (string) $xml->permissions->permission[2]['scope']);
        $this->assertEquals('UGROUP_5', (string) $xml->permissions->permission[2]['ugroup']);
        $this->assertEquals('PERM_3', (string) $xml->permissions->permission[2]['type']);
    }

    public function testItExportsTheTrackerID()
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals('T110', (string) $attributes['id']);
    }

    public function testItExportsNoParentIfNotInAHierarchy()
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals("0", (string) $attributes['parent_id']);
    }

    public function testItExportsTheParentId()
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn(array());
        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $this->hierarchy->addRelationship(9001, 110);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals("T9001", (string) $attributes['parent_id']);
    }

    public function testItExportsTheTrackerColor()
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn(array());

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $color = $xml->color;
        $this->assertEquals(TrackerColor::default()->getName(), (string) $color);
    }
}
