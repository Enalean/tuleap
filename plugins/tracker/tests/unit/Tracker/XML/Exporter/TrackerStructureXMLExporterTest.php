<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter;

use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker;
use Tracker_RulesManager;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedChecker;
use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\RetrieveFormElementsForTrackerStub;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Webhook\WebhookXMLExporter;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowXMLExporter;
use UserXMLExporter;

final class TrackerStructureXMLExporterTest extends TestCase
{
    private const TRACKER_ID = 110;

    public function testPermissionsExport(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [
                1   => ['PERM_1'],
                3   => ['PERM_2'],
                5   => ['PERM_3'],
                115 => ['PERM_3'],
            ],
        );

        $ugroups = [
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5,
        ];

        $ugroup_retriever->method('getProjectUgroupIds')->willReturn($ugroups);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        self::assertTrue(isset($xml->permissions));
        self::assertEquals('tracker', (string) $xml->permissions->permission[0]['scope']);
        self::assertEquals('UGROUP_1', (string) $xml->permissions->permission[0]['ugroup']);
        self::assertEquals('PERM_1', (string) $xml->permissions->permission[0]['type']);

        self::assertEquals('tracker', (string) $xml->permissions->permission[1]['scope']);
        self::assertEquals('UGROUP_3', (string) $xml->permissions->permission[1]['ugroup']);
        self::assertEquals('PERM_2', (string) $xml->permissions->permission[1]['type']);

        self::assertEquals('tracker', (string) $xml->permissions->permission[2]['scope']);
        self::assertEquals('UGROUP_5', (string) $xml->permissions->permission[2]['ugroup']);
        self::assertEquals('PERM_3', (string) $xml->permissions->permission[2]['type']);
    }

    public function testItExportsTheTrackerID(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertEquals('T110', (string) $attributes['id']);
    }

    public function testItExportsNoParentIfNotInAHierarchy(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertEquals("0", (string) $attributes['parent_id']);
    }

    public function testItExportsTheParentId(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            TrackerTestBuilder::aTracker()->withId(9001)->build(),
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertEquals("T9001", (string) $attributes['parent_id']);
    }

    public function testItExportsTheTrackerColor(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $color = $xml->color;
        self::assertEquals(TrackerColor::default()->getName(), (string) $color);
    }

    public function testItExportTheTrackerUsageInNewDropDown(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertTrue(isset($attributes['is_displayed_in_new_dropdown']));
        self::assertEquals(1, (int) $attributes['is_displayed_in_new_dropdown']);
    }

    public function testItDoesNotExportWhenTrackerUsePrivateComment(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(true);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertFalse(isset($attributes['use_private_comments']));
    }

    public function testItExportsWhenTrackerDoesNotUsePrivateComment(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(false);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertTrue(isset($attributes['use_private_comments']));
        self::assertEquals("0", (string) $attributes['use_private_comments']);
    }

    public function testItDoesNotExportCalendarEventConfig(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(false);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertFalse(isset($attributes['should_send_event_in_notification']));
    }

    public function testItExportCalendarEventConfig(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(false);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertTrue(isset($attributes['should_send_event_in_notification']));
        self::assertSame("1", (string) $attributes['should_send_event_in_notification']);
    }

    public function testItExportsAllowedMoveAction(): void
    {
        $ugroup_retriever = $this->createMock(UGroupRetrieverWithLegacy::class);
        $ugroup_retriever->method('getProjectUgroupIds')->willReturn([]);

        $private_comment_enable_dao = $this->createMock(TrackerPrivateCommentUGroupEnabledDao::class);
        $private_comment_enable_dao
            ->method('isTrackerEnabledPrivateComment')
            ->with(self::TRACKER_ID)
            ->willReturn(false);

        $tracker = $this->buildTrackerMock(
            $ugroup_retriever,
            null,
            [],
        );

        $base_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');

        $mapping = [];
        $xml     = $this->buildTrackerStructureXMLExporter(
            $private_comment_enable_dao,
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
        )->exportTrackerStructureToXML(
            $tracker,
            $base_xml,
            $this->createMock(UserXMLExporter::class),
            $mapping,
            false,
        );

        $attributes = $xml->attributes();
        self::assertTrue(isset($attributes['enable_move_artifacts']));
        self::assertSame("1", (string) $attributes['enable_move_artifacts']);
    }

    private function buildTrackerStructureXMLExporter(
        TrackerPrivateCommentUGroupEnabledDao $private_comment_enable_dao,
        CheckEventShouldBeSentInNotificationStub $check_event_should_be_sent_in_notification_stub,
    ): TrackerStructureXMLExporter {
        $dropdown_dao = $this->createMock(PromotedTrackerDao::class);
        $dropdown_dao->method('isContaining')->willReturn(true);

        $canned_response_factory = $this->createMock(\Tracker_CannedResponseFactory::class);
        $canned_response_factory->method('getCannedResponses')->willReturn([]);

        $rules_manager = $this->createMock(Tracker_RulesManager::class);
        $rules_manager->method('exportToXml');

        $report_factory = $this->createMock(\Tracker_ReportFactory::class);
        $report_factory->method('getReportsByTrackerId')->willReturn([]);

        $workflow_factory = $this->createMock(\WorkflowFactory::class);
        $workflow_factory->method('getWorkflowByTrackerId')->willReturn(null);

        $webhook_xml_exporter = $this->createMock(WebhookXMLExporter::class);
        $webhook_xml_exporter->expects(self::once())->method('exportTrackerWebhooksInXML');

        $move_action_allowed_dao = $this->createMock(MoveActionAllowedDAO::class);
        $move_action_allowed_dao->method('isMoveActionAllowedInTracker')->willReturn(true);

        return new TrackerStructureXMLExporter(
            $dropdown_dao,
            $private_comment_enable_dao,
            $check_event_should_be_sent_in_notification_stub,
            $canned_response_factory,
            RetrieveFormElementsForTrackerStub::withoutAnyElements(),
            $rules_manager,
            $report_factory,
            $workflow_factory,
            $this->createMock(SimpleWorkflowXMLExporter::class),
            $webhook_xml_exporter,
            new MoveActionAllowedChecker($move_action_allowed_dao)
        );
    }

    private function buildTrackerMock(
        UGroupRetrieverWithLegacy $u_group_retriever_with_legacy,
        ?Tracker $parent_tracker,
        array $ugroups,
    ): Tracker&MockObject {
        $tracker = $this->createMock(Tracker::class);

        $semantic_manager = $this->createMock(\Tracker_SemanticManager::class);
        $semantic_manager->method('exportToXml');

        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('getParent')->willReturn($parent_tracker);
        $tracker->method('getXMLId')->willReturn('T110');
        $tracker->method('getItemName')->willReturn('bug');
        $tracker->method('getName')->willReturn('Bugs');
        $tracker->method('getDescription')->willReturn('');
        $tracker->method('getColor')->willReturn(TrackerColor::default());
        $tracker->method('isEmailgatewayEnabled')->willReturn(false);
        $tracker->method('isCopyAllowed')->willReturn(false);
        $tracker->method('getNotificationsLevel')->willReturn(0);
        $tracker->method('getTrackerSemanticManager')->willReturn($semantic_manager);
        $tracker->method('getUGroupRetrieverWithLegacy')->willReturn($u_group_retriever_with_legacy);
        $tracker->method('getPermissionsByUgroupId')->willReturn($ugroups);

        return $tracker;
    }
}
