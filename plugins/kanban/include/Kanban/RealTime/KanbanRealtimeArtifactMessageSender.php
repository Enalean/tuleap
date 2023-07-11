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
declare(strict_types=1);
namespace Tuleap\Kanban\RealTime;

use Tuleap\Kanban\RealTimeMercure\RealTimeArtifactMessageControllerMercure;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Tracker\Artifact\Artifact;

class KanbanRealtimeArtifactMessageSender
{
    public function __construct(
        private readonly RealTimeArtifactMessageControllerMercure $realtime_mercure_controller,
        private readonly RealTimeArtifactMessageController $realtime_controller,
    ) {
    }

    public function sendMessageArtifact(Artifact $artifact, \PFUser $user, string $event): void
    {
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            $this->realtime_mercure_controller->sendMessageForKanban(
                $artifact,
                $event
            );
        } else {
            $this->realtime_controller->sendMessageForKanban(
                $user,
                $artifact,
                $event
            );
        }
    }
}
