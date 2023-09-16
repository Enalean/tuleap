<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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
namespace Tuleap\Kanban\RealTime;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use TrackerFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\RealTimeMercure\KanbanStructureRealTimeMercure;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class KanbanRealtimeMessageSenderTest extends TestCase
{
    use ForgeConfigSandbox;

    private TrackerFactory&MockObject $tracker_factory;
    private KanbanStructureRealTimeMercure&MockObject $structure_realtime_kanban;
    private NodeJSClient&MockObject $node_js_client;
    private \Tracker_Permission_PermissionsSerializer&MockObject $permissions_serializer;
    private Kanban&MockObject $kanban;
    private \PFUser $user;
    private KanbanRealtimeStructureMessageSender $sender;
    private Tracker $tracker;
    private \HTTPRequest&MockObject $request;
    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker_factory           = $this->createMock(TrackerFactory::class);
        $this->structure_realtime_kanban = $this->createMock(KanbanStructureRealTimeMercure::class);
        $this->node_js_client            = $this->createMock(NodeJSClient::class);
        $this->permissions_serializer    = $this->createMock(\Tracker_Permission_PermissionsSerializer::class);
        $this->tracker                   = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->kanban                    = $this->createMock(Kanban::class);
        $this->user                      = ProvideCurrentUserStub::buildCurrentUserByDefault()->getCurrentUser();
        $this->request                   = $this->createMock(\HTTPRequest::class);
        $this->kanban->method('getTrackerId')->willReturn(1);
        $this->sender = new KanbanRealtimeStructureMessageSender($this->tracker_factory, $this->structure_realtime_kanban, $this->node_js_client, $this->permissions_serializer);
    }

    public function testNoFeatureFlag(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, 0);
        $this->kanban->method('getId')->willReturn(1);
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->permissions_serializer->method('getLiteralizedAllUserGroupsThatCanViewTracker')->willReturn(['a']);
        $this->node_js_client->expects($this->once())->method('sendMessage');
        $this->structure_realtime_kanban->expects($this->never())->method('sendStructureUpdate');
        $this->request->method('getFromServer')->willReturn('1');
        $this->sender->sendMessageStructure($this->kanban, 'test', $this->user, $this->request, 'test');
    }

    public function testFeatureFlag(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->node_js_client->expects($this->never())->method('sendMessage');
        $this->structure_realtime_kanban->expects($this->once())->method('sendStructureUpdate');
        $this->request->method('getFromServer')->willReturn('1');
        $this->sender->sendMessageStructure($this->kanban, 'test', $this->user, $this->request, 'test');
    }

    public function testNoUUID(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->node_js_client->expects($this->never())->method('sendMessage');
        $this->structure_realtime_kanban->expects($this->never())->method('sendStructureUpdate');
        $this->request->method('getFromServer')->willReturn(false);
        $this->sender->sendMessageStructure($this->kanban, 'test', $this->user, $this->request, 'test');
    }

    public function testNoTracker(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);
        $this->node_js_client->expects($this->never())->method('sendMessage');
        $this->structure_realtime_kanban->expects($this->never())->method('sendStructureUpdate');
        $this->request->method('getFromServer')->willReturn(1);
        $this->expectException(\RuntimeException::class);
        $this->sender->sendMessageStructure($this->kanban, 'test', $this->user, $this->request, 'test');
    }
}
