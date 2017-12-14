<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\RealTime;

use PFUser;
use Tracker_Artifact;
use Tracker_Permission_PermissionsSerializer;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\ArtifactRightsPresenter;
use Tuleap\TestManagement\REST\v1\BugRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\User\REST\UserRepresentation;

class RealTimeMessageSender
{
    const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    /** @var  NodeJSClient */
    private $node_js_client;
    /**
     * @var Tracker_Permission_PermissionsSerializer
     */
    private $permissions_serializer;
    /**
     * @var ArtifactFactory
     */
    private $testmanagement_artifact_factory;

    public function __construct(
        NodeJSClient $node_js_client,
        Tracker_Permission_PermissionsSerializer $permissions_serializer,
        ArtifactFactory $testmanagement_artifact_factory
    ) {
        $this->node_js_client                   = $node_js_client;
        $this->permissions_serializer           = $permissions_serializer;
        $this->testmanagement_artifact_factory  = $testmanagement_artifact_factory;
    }

    public function sendExecutionCreated(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact
    ) {
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $this->sendExecution($user, $campaign, $artifact, 'testmanagement_execution:create', $data);
    }

    public function sendExecutionDeleted(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact
    ) {
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $this->sendExecution($user, $campaign, $artifact, 'testmanagement_execution:delete', $data);
    }

    public function sendExecutionUpdated(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact,
        $status,
        $previous_status,
        $previous_user
    ) {
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id'     => $artifact->getId(),
            'status'          => $status,
            'previous_status' => $previous_status,
            'user'            => $user_representation,
            'previous_user'   => $previous_user
        );
        $this->sendExecution($user, $campaign, $artifact, 'testmanagement_execution:update', $data);
    }

    public function sendArtifactLinkAdded(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $execution_artifact,
        Tracker_Artifact $linked_artifact
    ) {
        if (! isset($_SERVER[self::HTTP_CLIENT_UUID])
            || ! $_SERVER[self::HTTP_CLIENT_UUID]
        ) {
            return;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id'         => $execution_artifact->getId(),
            'added_artifact_link' => $this->buildArtifactLinkRepresentation($linked_artifact)
        );

        $rights   = new ArtifactRightsPresenter($linked_artifact, $this->permissions_serializer);
        $message  = new MessageDataPresenter(
            $user->getId(),
            $_SERVER[self::HTTP_CLIENT_UUID],
            'testmanagement_' . $campaign->getId(),
            $rights,
            'testmanagement_execution:link_artifact',
            $data
        );

        $this->node_js_client->sendMessage($message);
    }

    public function sendCampaignUpdated(
        PFUser $user,
        Tracker_Artifact $artifact
    ) {
        if (! isset($_SERVER[self::HTTP_CLIENT_UUID])
            || ! $_SERVER[self::HTTP_CLIENT_UUID]
        ) {
            return;
        }
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $rights  = new ArtifactRightsPresenter($artifact, $this->permissions_serializer);
        $message = new MessageDataPresenter(
            $user->getId(),
            $_SERVER[self::HTTP_CLIENT_UUID],
            'testmanagement_' . $artifact->getId(),
            $rights,
            'testmanagement_campaign:update',
            $data
        );

        $this->node_js_client->sendMessage($message);
    }

    public function sendPresences(Tracker_Artifact $campaign, Tracker_Artifact $artifact, PFUser $user, $uuid, $remove_from)
    {
        if (! isset($_SERVER[self::HTTP_CLIENT_UUID])
            || ! $_SERVER[self::HTTP_CLIENT_UUID]
        ) {
            return;
        }
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'presence' => array(
                'execution_id' => $artifact->getId(),
                'uuid'         => $uuid,
                'remove_from'  => $remove_from,
                'user'         => $user_representation
            )
        );
        $rights  = new ArtifactRightsPresenter($artifact, $this->permissions_serializer);
        $message = new MessageDataPresenter(
            $user->getId(),
            $_SERVER[self::HTTP_CLIENT_UUID],
            'testmanagement_' . $campaign->getId(),
            $rights,
            'testmanagement_user:presence',
            $data
        );

        $this->node_js_client->sendMessage($message);
    }

    private function buildArtifactLinkRepresentation(Tracker_Artifact $artifact_link)
    {
        $tracker_representation = new MinimalTrackerRepresentation();
        $tracker_representation->build($artifact_link->getTracker());

        $artifact_link_representation = new BugRepresentation();
        $artifact_link_representation->build($artifact_link, $tracker_representation);

        return $artifact_link_representation;
    }

    private function sendExecution(PFUser $user, Tracker_Artifact $campaign, Tracker_Artifact $artifact, $event_name, $data)
    {
        if (! isset($_SERVER[self::HTTP_CLIENT_UUID])
            || ! $_SERVER[self::HTTP_CLIENT_UUID]
        ) {
            return;
        }
        $rights  = new ArtifactRightsPresenter($artifact, $this->permissions_serializer);
        $message = new MessageDataPresenter(
            $user->getId(),
            $_SERVER[self::HTTP_CLIENT_UUID],
            'testmanagement_' . $campaign->getId(),
            $rights,
            $event_name,
            $data
        );

        $this->node_js_client->sendMessage($message);
    }
}
