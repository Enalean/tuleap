<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\RealTime;

use PFUser;
use Tracker_Permission_PermissionsSerializer;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\Tracker\Artifact\Artifact;

class RealTimeArtifactMessageSender
{
    public const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    /** @var  NodeJSClient */
    private $node_js_client;
    /**
     * @var Tracker_Permission_PermissionsSerializer
     */
    private $permissions_serializer;

    public function __construct(
        NodeJSClient $node_js_client,
        Tracker_Permission_PermissionsSerializer $permissions_serializer
    ) {
        $this->node_js_client         = $node_js_client;
        $this->permissions_serializer = $permissions_serializer;
    }

    public function sendMessage(
        PFUser $user,
        Artifact $artifact,
        array $data,
        $event_name,
        $room_id
    ) {
        $rights  = new ArtifactRightsPresenter($artifact, $this->permissions_serializer);
        $message = new MessageDataPresenter(
            $user->getId(),
            isset($_SERVER[self::HTTP_CLIENT_UUID]) ? $_SERVER[self::HTTP_CLIENT_UUID] : null,
            $room_id,
            $rights,
            $event_name,
            $data
        );

        $this->node_js_client->sendMessage($message);
    }
}
