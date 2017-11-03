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
use Tuleap\TestManagement\REST\v1\ExecutionRepresentationBuilder;
use Tuleap\TestManagement\REST\v1\ExecutionsResource;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\User\REST\UserRepresentation;

class RealTimeMessageSender
{
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
    /**
     * @var ExecutionRepresentationBuilder
     */
    private $execution_representation_builder;

    public function __construct(
        NodeJSClient $node_js_client,
        Tracker_Permission_PermissionsSerializer $permissions_serializer,
        ArtifactFactory $testmanagement_artifact_factory,
        ExecutionRepresentationBuilder $execution_representation_builder
    ) {
        $this->node_js_client                   = $node_js_client;
        $this->permissions_serializer           = $permissions_serializer;
        $this->testmanagement_artifact_factory  = $testmanagement_artifact_factory;
        $this->execution_representation_builder = $execution_representation_builder;
    }

    public function sendArtifactLinkAdded(
        PFUser $user,
        Tracker_Artifact $execution_artifact,
        Tracker_Artifact $linked_artifact
    ) {
        if (! isset($_SERVER[ExecutionsResource::HTTP_CLIENT_UUID])
            || ! $_SERVER[ExecutionsResource::HTTP_CLIENT_UUID]
        ) {
            return;
        }

        $execution_representation = $this->execution_representation_builder->getExecutionRepresentation(
            $user,
            $execution_artifact
        );

        $added_artifact_link_representation = $this->buildArtifactLinkRepresentation($linked_artifact);

        $user_representation = new UserRepresentation();
        $user_representation->build($user);
        $data = array(
            'execution'           => $execution_representation,
            'added_artifact_link' => $added_artifact_link_representation
        );

        $rights   = new ArtifactRightsPresenter($linked_artifact, $this->permissions_serializer);
        $campaign = $this->testmanagement_artifact_factory->getCampaignForExecution($execution_artifact);
        $message  = new MessageDataPresenter(
            $user->getId(),
            $_SERVER[ExecutionsResource::HTTP_CLIENT_UUID],
            'testmanagement_' . $campaign->getId(),
            $rights,
            'testmanagement_execution:link_artifact',
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
}
