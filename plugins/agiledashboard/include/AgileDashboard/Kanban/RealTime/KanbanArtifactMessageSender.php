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

namespace Tuleap\AgileDashboard\Kanban\RealTime;

use BackendLogger;
use PFUser;
use Tracker_Artifact;
use Tracker_Permission_PermissionsSerializer;
use Tuleap\AgileDashboard\RealTime\RealTimeArtifactMessageException;
use Tuleap\AgileDashboard\RealTime\RealTimeArtifactMessageSender;
use Tuleap\AgileDashboard\REST\v1\Kanban\ItemRepresentationBuilder;
use Tuleap\RealTime\NodeJSClient;

class KanbanArtifactMessageSender
{
    const EVENT_NAME_ARTIFACT_CREATED = 'kanban_item:create';
    const EVENT_NAME_ARTIFACT_UPDATED = 'kanban_item:update';
    const EVENT_NAME_ARTIFACT_MOVED   = 'kanban_item:move';

    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var KanbanArtifactMessageBuilder
     */
    private $kanban_artifact_message_builder;
    /**
     * @var RealTimeArtifactMessageSender
     */
    private $artifact_message_sender;
    /**
     * @var BackendLogger
     */
    private $backend_logger;

    public function __construct(
        RealTimeArtifactMessageSender $artifact_message_sender,
        ItemRepresentationBuilder $item_representation_builder,
        KanbanArtifactMessageBuilder $kanban_artifact_message_builder,
        BackendLogger $backend_logger
    ) {
        $this->artifact_message_sender         = $artifact_message_sender;
        $this->item_representation_builder     = $item_representation_builder;
        $this->kanban_artifact_message_builder = $kanban_artifact_message_builder;
        $this->backend_logger                  = $backend_logger;
    }

    public function sendMessageArtifactCreated(PFUser $user, Tracker_Artifact $artifact, $kanban_id)
    {
        $item = $this->item_representation_builder->buildItemRepresentation($artifact);

        $item->card_fields[] = $artifact->getTracker()->getTitleField();
        $item->card_fields[] = $artifact->getTracker()->getStatusField();

        $data = array(
            'artifact' => $item
        );

        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            self::EVENT_NAME_ARTIFACT_CREATED,
            $kanban_id
        );
    }

    public function sendMessageArtifactUpdated(PFUser $user, Tracker_Artifact $artifact, $kanban_id)
    {
        $item = $this->item_representation_builder->buildItemRepresentation($artifact);

        $item->card_fields[] = $artifact->getTracker()->getTitleField();
        $item->card_fields[] = $artifact->getTracker()->getStatusField();

        $data = array(
            'artifact' => $item
        );

        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            self::EVENT_NAME_ARTIFACT_UPDATED,
            $kanban_id
        );
    }

    public function sendMessageArtifactMoved(PFUser $user, Tracker_Artifact $artifact, $kanban_id)
    {
        try {
            $data = (array) $this->kanban_artifact_message_builder->buildArtifactMoved($artifact);
            $this->artifact_message_sender->sendMessage(
                $user,
                $artifact,
                $data,
                self::EVENT_NAME_ARTIFACT_MOVED,
                $kanban_id
            );
        } catch (RealTimeArtifactMessageException $exception) {
            $this->backend_logger->debug($exception->getMessage());
        }
    }

    public function sendMessageArtifactReordered(PFUser $user, $artifact, $kanban_id)
    {
        try {
            $data = (array) $this->kanban_artifact_message_builder->buildArtifactReordered($artifact);
            $this->artifact_message_sender->sendMessage(
                $user,
                $artifact,
                $data,
                self::EVENT_NAME_ARTIFACT_MOVED,
                $kanban_id
            );
        } catch (RealTimeArtifactMessageException $exception) {
            $this->backend_logger->debug($exception->getMessage());
        }
    }
}
