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

declare(strict_types=1);

namespace Tuleap\Tracker\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use SimpleXMLElement;
use Tracker;
use Tracker_CannedResponseManager;
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tracker_RulesManager;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Webhook\WebhookXMLExporter;
use UserManager;

final class TrackerExportToXmlTest extends \Tuleap\Test\PHPUnit\TestCase
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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerPrivateCommentUGroupEnabledDao
     */
    private $private_comment_enable_dao;
    /*
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerInNewDropdownDao
     */
    private $dropdown_dao;

    protected function setUp(): void
    {
        $this->tracker     = Mockery::mock(Tracker::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->tracker->id = 110;
        $this->tracker->shouldReceive('getColor')->andReturn(TrackerColor::default());
        $this->tracker->shouldReceive('getUserManager')->andReturn(Mockery::mock(UserManager::class));
        $this->tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));
        $this->tracker->shouldReceive('getItemName')->andReturn('bug');
        $this->tracker->shouldReceive('getName')->andReturn('Bugs');
        $this->tracker->shouldReceive('getDescription')->andReturn('');

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

        $this->dropdown_dao = Mockery::mock(PromotedTrackerDao::class);
        $this->dropdown_dao->shouldReceive('isContaining')->andReturnTrue()->once()->byDefault();
        $this->tracker->shouldReceive('getDropDownDao')->andReturn($this->dropdown_dao);

        $this->private_comment_enable_dao = Mockery::mock(TrackerPrivateCommentUGroupEnabledDao::class);
        $this->private_comment_enable_dao
            ->shouldReceive('isTrackerEnabledPrivateComment')
            ->with($this->tracker->id)
            ->andReturnTrue()
            ->once()
            ->byDefault();
        $this->tracker->shouldReceive('getPrivateCommentEnabledDao')->andReturn($this->private_comment_enable_dao);
    }

    public function testPermissionsExport(): void
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

    public function testItExportsTheTrackerID(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals('T110', (string) $attributes['id']);
    }

    public function testItExportsNoParentIfNotInAHierarchy(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals("0", (string) $attributes['parent_id']);
    }

    public function testItExportsTheParentId(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);
        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $this->tracker->setParent(Mockery::mock(Tracker::class, ['getXMLId' => 'T9001']));

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertEquals("T9001", (string) $attributes['parent_id']);
    }

    public function testItExportsTheTrackerColor(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $color = $xml->color;
        $this->assertEquals(TrackerColor::default()->getName(), (string) $color);
    }

    public function testItExportTheTrackerUsageInNewDropDown(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertTrue(isset($attributes['is_displayed_in_new_dropdown']));
        $this->assertEquals(1, (int) $attributes['is_displayed_in_new_dropdown']);
    }

    public function testItDoesNotExportTheTrackerUsageInNewDropDownIfDontUse(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $this->dropdown_dao->shouldReceive('isContaining')->andReturnFalse();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertFalse(isset($attributes['is_displayed_in_new_dropdown']));
    }

    public function testItDoesNotExportWhenTrackerUsePrivateComment(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertFalse(isset($attributes['use_private_comments']));
    }

    public function testItExportsWhenTrackerDoesNotUsePrivateComment(): void
    {
        $this->formelement_factory->shouldReceive('getUsedFormElementForTracker')->andReturn([]);

        $this->ugroup_retriever->shouldReceive('getProjectUgroupIds')->andReturn([]);
        $this->tracker->shouldReceive('getPermissionsByUgroupId')->andReturn([]);

        $this->private_comment_enable_dao
            ->shouldReceive('isTrackerEnabledPrivateComment')
            ->with($this->tracker->id)
            ->andReturnFalse()
            ->once();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $xml = $this->tracker->exportToXML($xml);

        $attributes = $xml->attributes();
        $this->assertTrue(isset($attributes['use_private_comments']));

        $this->assertEquals(0, (string) $attributes['use_private_comments']);
    }
}
