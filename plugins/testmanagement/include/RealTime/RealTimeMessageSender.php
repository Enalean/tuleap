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

namespace Tuleap\TestManagement\RealTime;

use PFUser;
use Tracker_Artifact;
use Tracker_Permission_PermissionsSerializer;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\TestManagement\REST\v1\BugRepresentation;
use Tuleap\Tracker\RealTime\ArtifactRightsPresenter;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\User\REST\UserRepresentation;

class RealTimeMessageSender
{
    public const HTTP_CLIENT_UUID             = 'HTTP_X_CLIENT_UUID';
    public const EVENT_NAME_EXECUTION_CREATED = 'testmanagement_execution:create';
    public const EVENT_NAME_EXECUTION_DELETED = 'testmanagement_execution:delete';
    public const EVENT_NAME_EXECUTION_UPDATED = 'testmanagement_execution:update';
    public const EVENT_NAME_ARTIFACT_LINKED   = 'testmanagement_execution:link_artifact';
    public const EVENT_NAME_CAMPAIGN_UPDATED  = 'testmanagement_campaign:update';
    public const EVENT_NAME_USER_PRESENCE     = 'testmanagement_user:presence';

    /** @var  NodeJSClient */
    private $node_js_client;
    /**
     * @var Tracker_Permission_PermissionsSerializer
     */
    private $permissions_serializer;
    /**
     * @var RealTimeArtifactMessageSender
     */
    private $artifact_message_sender;

    public function __construct(
        NodeJSClient $node_js_client,
        Tracker_Permission_PermissionsSerializer $permissions_serializer,
        RealTimeArtifactMessageSender $artifact_message_sender
    ) {
        $this->node_js_client                  = $node_js_client;
        $this->permissions_serializer          = $permissions_serializer;
        $this->artifact_message_sender         = $artifact_message_sender;
    }

    public function sendExecutionCreated(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact
    ): void {
        if ($this->doesNotHaveHTTPClientUUID()) {
            return;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_CREATED, $data);
    }

    public function sendExecutionDeleted(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact
    ): void {
        if ($this->doesNotHaveHTTPClientUUID()) {
            return;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_DELETED, $data);
    }

    public function sendExecutionUpdated(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact,
        ?string $status,
        ?string $previous_status,
        ?UserRepresentation $previous_user
    ): void {
        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id'     => $artifact->getId(),
            'status'          => $status,
            'previous_status' => $previous_status,
            'user'            => $user_representation,
            'previous_user'   => $previous_user
        );
        $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_UPDATED, $data);
    }

    public function sendArtifactLinkAdded(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $execution_artifact,
        Tracker_Artifact $linked_artifact
    ): void {
        if ($this->doesNotHaveHTTPClientUUID()) {
            return;
        }

        $data = array(
            'artifact_id'         => $execution_artifact->getId(),
            'added_artifact_link' => $this->buildArtifactLinkRepresentation($linked_artifact)
        );

        $this->artifact_message_sender->sendMessage(
            $user,
            $linked_artifact,
            $data,
            self::EVENT_NAME_ARTIFACT_LINKED,
            'testmanagement_' . $campaign->getId()
        );
    }

    public function sendCampaignUpdated(
        PFUser $user,
        Tracker_Artifact $artifact
    ): void {
        if ($this->doesNotHaveHTTPClientUUID()) {
            return;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'artifact_id' => $artifact->getId(),
            'user'        => $user_representation,
        );
        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            self::EVENT_NAME_CAMPAIGN_UPDATED,
            'testmanagement_' . $artifact->getId()
        );
    }

    public function sendPresences(
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact,
        PFUser $user,
        string $uuid,
        string $remove_from
    ): void {
        if ($this->doesNotHaveHTTPClientUUID()) {
            return;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data    = array(
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
            self::EVENT_NAME_USER_PRESENCE,
            $data
        );

        $this->node_js_client->sendMessage($message);
    }

    private function buildArtifactLinkRepresentation(Tracker_Artifact $artifact_link): BugRepresentation
    {
        $tracker_representation = new MinimalTrackerRepresentation();
        $tracker_representation->build($artifact_link->getTracker());

        $artifact_link_representation = new BugRepresentation();
        $artifact_link_representation->build($artifact_link, $tracker_representation);

        return $artifact_link_representation;
    }

    private function sendExecution(
        PFUser $user,
        Tracker_Artifact $campaign,
        Tracker_Artifact $artifact,
        string $event_name,
        array $data
    ): void {
        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            $event_name,
            'testmanagement_' . $campaign->getId()
        );
    }

    private function doesNotHaveHTTPClientUUID(): bool
    {
        return ! isset($_SERVER[self::HTTP_CLIENT_UUID]) || ! $_SERVER[self::HTTP_CLIENT_UUID];
    }
}
