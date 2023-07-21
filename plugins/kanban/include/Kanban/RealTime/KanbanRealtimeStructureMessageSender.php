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

use TrackerFactory;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanRightsPresenter;
use Tuleap\Kanban\RealTimeMercure\KanbanStructureRealTimeMercure;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\MercureClient;

class KanbanRealtimeStructureMessageSender
{
    public const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';
    public function __construct(
        private readonly TrackerFactory $tracker_factory,
        private readonly KanbanStructureRealTimeMercure $structure_realtime_kanban,
        private readonly NodeJSClient $node_js_client,
        private readonly \Tracker_Permission_PermissionsSerializer $permissions_serializer,
    ) {
    }

    public function sendMessageStructure(
        Kanban $kanban,
        string $kanban_cmd,
        \PFUser $user,
        \HTTPRequest $request,
        mixed $data,
    ): void {
        if (! $request->getFromServer(self::HTTP_CLIENT_UUID)) {
            return;
        }
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            $this->structure_realtime_kanban->sendStructureUpdate($kanban);
        } else {
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $message = new MessageDataPresenter(
                $user->getId(),
                $request->getFromServer(self::HTTP_CLIENT_UUID),
                $kanban->getId(),
                $rights,
                $kanban_cmd,
                $data
            );
            $this->node_js_client->sendMessage($message);
        }
    }
}
