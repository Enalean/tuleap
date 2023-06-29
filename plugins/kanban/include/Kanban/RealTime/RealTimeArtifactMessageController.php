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

use Tuleap\Kanban\KanbanFactory;
use PFUser;
use Tuleap\Tracker\Artifact\Artifact;

final class RealTimeArtifactMessageController
{
    public const EVENT_NAME_ARTIFACT_CREATED   = 'created';
    public const EVENT_NAME_ARTIFACT_UPDATED   = 'updated';
    public const EVENT_NAME_ARTIFACT_REORDERED = 'reordered';

    public function __construct(
        private readonly KanbanFactory $kanban_factory,
        private readonly KanbanArtifactMessageSender $kanban_artifact_message_sender,
    ) {
    }

    public function sendMessageForKanban(PFUser $user, Artifact $artifact, string $event_name_artifact): void
    {
        $kanban_id = $this->kanban_factory->getKanbanIdByTrackerId($artifact->getTrackerId());

        if (! $kanban_id) {
            return;
        }

        switch ($event_name_artifact) {
            case self::EVENT_NAME_ARTIFACT_CREATED:
                $this->kanban_artifact_message_sender->sendMessageArtifactCreated(
                    $user,
                    $artifact,
                    $kanban_id
                );
                break;
            case self::EVENT_NAME_ARTIFACT_UPDATED:
                $this->kanban_artifact_message_sender->sendMessageArtifactUpdated(
                    $user,
                    $artifact,
                    $kanban_id
                );

                $this->kanban_artifact_message_sender->sendMessageArtifactMoved(
                    $user,
                    $artifact,
                    $kanban_id
                );
                break;
            case self::EVENT_NAME_ARTIFACT_REORDERED:
                $this->kanban_artifact_message_sender->sendMessageArtifactReordered(
                    $user,
                    $artifact,
                    $kanban_id
                );
                break;
        }
    }
}
