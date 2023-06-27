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

namespace Tuleap\Kanban\RealTime;

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageException;
use Tuleap\Tracker\RealTime\RealTimeArtifactMessageSender;

final class KanbanArtifactMessageSender
{
    public const EVENT_NAME_ARTIFACT_CREATED = 'kanban_item:create';
    public const EVENT_NAME_ARTIFACT_UPDATED = 'kanban_item:update';
    public const EVENT_NAME_ARTIFACT_MOVED   = 'kanban_item:move';

    public function __construct(
        private readonly RealTimeArtifactMessageSender $artifact_message_sender,
        private readonly KanbanArtifactMessageBuilder $kanban_artifact_message_builder,
        private readonly LoggerInterface $backend_logger,
    ) {
    }

    public function sendMessageArtifactCreated(PFUser $user, Artifact $artifact, int $kanban_id): void
    {
        $this->sendMessageArtifact($user, $artifact, self::EVENT_NAME_ARTIFACT_CREATED, $kanban_id);
    }

    public function sendMessageArtifactUpdated(PFUser $user, Artifact $artifact, int $kanban_id): void
    {
        $this->sendMessageArtifact($user, $artifact, self::EVENT_NAME_ARTIFACT_UPDATED, $kanban_id);
    }

    public function sendMessageArtifact(PFUser $user, Artifact $artifact, string $event_name, int $kanban_id): void
    {
        $data = (array) $this->kanban_artifact_message_builder->buildArtifactUpdated($artifact);

        $this->artifact_message_sender->sendMessage(
            $user,
            $artifact,
            $data,
            $event_name,
            $kanban_id
        );
    }

    public function sendMessageArtifactMoved(PFUser $user, Artifact $artifact, int $kanban_id): void
    {
        try {
            $data = $this->kanban_artifact_message_builder->buildArtifactMoved($artifact);
            if ($data === null) {
                return;
            }
            $this->artifact_message_sender->sendMessage(
                $user,
                $artifact,
                (array) $data,
                self::EVENT_NAME_ARTIFACT_MOVED,
                $kanban_id
            );
        } catch (RealTimeArtifactMessageException $exception) {
            $this->backend_logger->debug($exception->getMessage());
        }
    }

    public function sendMessageArtifactReordered(PFUser $user, Artifact $artifact, int $kanban_id): void
    {
        try {
            $data = $this->kanban_artifact_message_builder->buildArtifactReordered($artifact);
            if ($data === null) {
                return;
            }
            $this->artifact_message_sender->sendMessage(
                $user,
                $artifact,
                (array) $data,
                self::EVENT_NAME_ARTIFACT_MOVED,
                $kanban_id
            );
        } catch (RealTimeArtifactMessageException $exception) {
            $this->backend_logger->debug($exception->getMessage());
        }
    }
}
