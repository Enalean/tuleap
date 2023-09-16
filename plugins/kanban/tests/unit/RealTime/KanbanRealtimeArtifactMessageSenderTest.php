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
namespace Tuleap\AgileDashboard\Kanban\RealTime;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Kanban\RealTime\KanbanRealtimeArtifactMessageSender;
use Tuleap\Kanban\RealTime\RealTimeArtifactMessageController;
use Tuleap\Kanban\RealTimeMercure\RealTimeArtifactMessageControllerMercure;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class KanbanRealtimeArtifactMessageSenderTest extends TestCase
{
    use ForgeConfigSandbox;

    private RealTimeArtifactMessageControllerMercure&MockObject $realtime_controller_mercure;
    private RealTimeArtifactMessageController&MockObject $realtime_controller;
    protected function setUp(): void
    {
        $this->realtime_controller         = $this->createMock(RealTimeArtifactMessageController::class);
        $this->realtime_controller_mercure = $this->createMock(RealTimeArtifactMessageControllerMercure::class);
        parent::setUp();
    }

    public function testNoFlag(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $event    = "event";
        $sender   = new KanbanRealtimeArtifactMessageSender(
            $this->realtime_controller_mercure,
            $this->realtime_controller
        );
        $this->realtime_controller->expects($this->once())->method('sendMessageForKanban');
        $this->realtime_controller_mercure->expects($this->never())->method('sendMessageForKanban');
        $sender->sendMessageArtifact(
            $artifact,
            $user,
            $event
        );
    }

    public function testFlag(): void
    {
        \ForgeConfig::setFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY, 1);
        $user     = UserTestBuilder::aUser()->build();
        $artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $event    = "event";
        $sender   = new KanbanRealtimeArtifactMessageSender(
            $this->realtime_controller_mercure,
            $this->realtime_controller
        );
        $this->realtime_controller->expects($this->never())->method('sendMessageForKanban');
        $this->realtime_controller_mercure->expects($this->once())->method('sendMessageForKanban');
        $sender->sendMessageArtifact(
            $artifact,
            $user,
            $event
        );
    }
}
