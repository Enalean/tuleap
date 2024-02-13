<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Tracker_Permission_PermissionsSerializer;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\RealTime\NodeJSClient;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\TestManagement\REST\v1\BugRepresentation;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\RealTime\ArtifactRightsPresenter;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;
use Tuleap\Tracker\RealtimeMercure\RealTimeMercureArtifactMessageSender;
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

    public const TEST_MANAGEMENT_TOPIC = 'TestManagement';

    public function __construct(
        private readonly NodeJSClient $node_js_client,
        private readonly Tracker_Permission_PermissionsSerializer $permissions_serializer,
        private readonly RealTimeArtifactMessageSender $artifact_message_sender,
        private readonly RealTimeMercureArtifactMessageSender $mercure_artifact_message_sender,
    ) {
    }

    public function sendExecutionCreated(
        PFUser $user,
        Artifact $campaign,
        Artifact $artifact,
        ?string $client_uuid,
    ): void {
        if ($this->doesNotHaveHTTPClientUUID($client_uuid)) {
            return;
        }
        $user_representation = UserRepresentation::build($user);
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $data = [
                'cmd'         => self::EVENT_NAME_EXECUTION_CREATED,
                'artifact_id' => $artifact->getId(),
                'user'        => $user_representation,
            ];
            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($campaign));
        } else {
            $data = [
                'artifact_id' => $artifact->getId(),
                'user'        => $user_representation,
            ];
            $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_CREATED, $data);
        }
    }

    public function sendExecutionDeleted(
        PFUser $user,
        Artifact $campaign,
        Artifact $artifact,
        ?string $client_uuid,
    ): void {
        if ($this->doesNotHaveHTTPClientUUID($client_uuid)) {
            return;
        }
        $user_representation = UserRepresentation::build($user);
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $data = [
                'cmd'         => self::EVENT_NAME_EXECUTION_DELETED,
                'artifact_id' => $artifact->getId(),
                'user'        => $user_representation,
            ];
            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($campaign));
        } else {
            $data = [
                'artifact_id' => $artifact->getId(),
                'user'        => $user_representation,
            ];
            $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_DELETED, $data);
        }
    }

    public function sendExecutionUpdated(
        PFUser $user,
        Artifact $campaign,
        Artifact $artifact,
        ?string $status,
        ?string $previous_status,
        ?UserRepresentation $previous_user,
    ): void {
        $user_representation = UserRepresentation::build($user);
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $data = [
                'cmd'           => self::EVENT_NAME_EXECUTION_UPDATED,
                'artifact_id'   => $artifact->getId(),
                'status'        => $status,
                'user'          => $user_representation,
                'previous_user' => $previous_user,
            ];
            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($campaign));
        } else {
            $data = [
                'artifact_id'     => $artifact->getId(),
                'status'          => $status,
                'previous_status' => $previous_status,
                'user'            => $user_representation,
                'previous_user'   => $previous_user,
            ];
            $this->sendExecution($user, $campaign, $artifact, self::EVENT_NAME_EXECUTION_UPDATED, $data);
        }
    }

    public function sendArtifactLinkAdded(
        PFUser $user,
        Artifact $campaign,
        Artifact $execution_artifact,
        Artifact $linked_artifact,
        ?string $client_uuid,
        MinimalTrackerRepresentation $tracker_representation,
    ): void {
        if ($this->doesNotHaveHTTPClientUUID($client_uuid)) {
            return;
        }

        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $data = [
                'cmd'                 => self::EVENT_NAME_ARTIFACT_LINKED,
                'artifact_id'         => $execution_artifact->getId(),
                'added_artifact_link' => $this->buildArtifactLinkRepresentation($linked_artifact, $tracker_representation),
            ];
            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($campaign));
        } else {
            $data = [
                'artifact_id'         => $execution_artifact->getId(),
                'added_artifact_link' => $this->buildArtifactLinkRepresentation($linked_artifact, $tracker_representation),
            ];

            $this->artifact_message_sender->sendMessage(
                $user,
                $linked_artifact,
                $data,
                self::EVENT_NAME_ARTIFACT_LINKED,
                'testmanagement_' . $campaign->getId()
            );
        }
    }

    public function sendCampaignUpdated(
        PFUser $user,
        Artifact $artifact,
        ?string $client_uuid,
    ): void {
        if ($this->doesNotHaveHTTPClientUUID($client_uuid)) {
            return;
        }

        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $data = [
                'cmd' => self::EVENT_NAME_CAMPAIGN_UPDATED,
                'artifact_id' => $artifact->getId(),
            ];
            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($artifact));
        } else {
            $user_representation = UserRepresentation::build($user);
            $data                = [
                'artifact_id' => $artifact->getId(),
                'user'        => $user_representation,
            ];
            $this->artifact_message_sender->sendMessage(
                $user,
                $artifact,
                $data,
                self::EVENT_NAME_CAMPAIGN_UPDATED,
                'testmanagement_' . $artifact->getId()
            );
        }
    }

    public function sendPresences(
        Artifact $campaign,
        Artifact $artifact,
        PFUser $user,
        string $uuid,
        string $remove_from,
        ?string $uuid_http,
    ): void {
        if ($this->doesNotHaveHTTPClientUUID($uuid_http)) {
            return;
        }

        $user_representation = UserRepresentation::build($user);
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY)) {
            $user_representation = new RealtimeUserRepresentation($user_representation, $uuid);
            $data                = [
                'cmd'          => self::EVENT_NAME_USER_PRESENCE,
                'execution_id' => $artifact->getId(),
                'uuid'         => $uuid,
                'remove_from'  => $remove_from,
                'user'         => $user_representation,
            ];

            $this->mercure_artifact_message_sender->sendMessage(json_encode($data), self::topicHelper($campaign));
        } else {
            $data    = [
                'presence' => [
                    'execution_id' => $artifact->getId(),
                    'uuid'         => $uuid,
                    'remove_from'  => $remove_from,
                    'user'         => $user_representation,
                ],
            ];
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
    }

    private function buildArtifactLinkRepresentation(Artifact $artifact_link, MinimalTrackerRepresentation $tracker_representation): BugRepresentation
    {
        $artifact_link_representation = new BugRepresentation();
        $artifact_link_representation->build($artifact_link, $tracker_representation);

        return $artifact_link_representation;
    }

    private function sendExecution(
        PFUser $user,
        Artifact $campaign,
        Artifact $artifact,
        string $event_name,
        array $data,
    ): void {
        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            $event_name,
            'testmanagement_' . $campaign->getId()
        );
    }

    private function doesNotHaveHTTPClientUUID(?string $client_uuid): bool
    {
        return ! isset($client_uuid) || ! $client_uuid;
    }

    public static function topicHelper(Artifact $artifact): string
    {
        return self::TEST_MANAGEMENT_TOPIC . '/' . $artifact->getId();
    }
}
