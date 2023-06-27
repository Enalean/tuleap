<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\RealTimeMercure;

use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\RealtimeMercure\RealTimeMercureArtifactMessageSender;

class KanbanArtifactMessageSenderMercure
{
    public const EVENT_NAME_ARTIFACT_CREATED = 'kanban_item:create';
    public const EVENT_NAME_ARTIFACT_UPDATED = 'kanban_item:update';
    public const EVENT_NAME_ARTIFACT_MOVED   = 'kanban_item:move';

    public const KANBAN_TOPIC = 'Kanban/';

    public function __construct(
        private readonly RealTimeMercureArtifactMessageSender $artifact_message_sender,
        private readonly KanbanArtifactMessageBuilderMercure $kanban_artifact_message_builder,
    ) {
    }

    public function sendMessageArtifactCreated(Artifact $artifact, int $kanban_id): void
    {
        $this->sendMessageArtifact($artifact, self::EVENT_NAME_ARTIFACT_CREATED, $kanban_id);
    }

    public function sendMessageArtifactUpdated(Artifact $artifact, int $kanban_id): void
    {
        $this->sendMessageArtifact($artifact, self::EVENT_NAME_ARTIFACT_UPDATED, $kanban_id);
    }

    public function sendMessageArtifact(Artifact $artifact, string $event_name, int $kanban_id): void
    {
        $data_artifact = (array) $this->kanban_artifact_message_builder->buildArtifactUpdated($artifact);
        $data          = KanbanArtifactMessagePresenterMercure::present($event_name, $data_artifact);
        $this->artifact_message_sender->sendMessage(
            $data,
            $this->topicHelper($kanban_id)
        );
    }

    public function sendMessageArtifactMoved(Artifact $artifact, int $kanban_id, Tracker_Semantic_Status $tracker_semantic): void
    {
        $data_artifact = $this->kanban_artifact_message_builder->buildArtifactMoved($artifact, $tracker_semantic);
        if ($data_artifact === null) {
            return;
        }
            $data = KanbanArtifactMessagePresenterMercure::present(self::EVENT_NAME_ARTIFACT_MOVED, $data_artifact->toArray());
            $this->artifact_message_sender->sendMessage(
                $data,
                $this->topicHelper($kanban_id)
            );
    }

    public function sendMessageArtifactReordered(Artifact $artifact, int $kanban_id, Tracker_Semantic_Status $tracker_semantic): void
    {
        $data_artifact = $this->kanban_artifact_message_builder->buildArtifactReordered($artifact, $tracker_semantic);
        if ($data_artifact === null) {
            return;
        }
            $data = KanbanArtifactMessagePresenterMercure::present(self::EVENT_NAME_ARTIFACT_MOVED, $data_artifact->toArray());
            $this->artifact_message_sender->sendMessage(
                $data,
                $this->topicHelper($kanban_id)
            );
    }

    public static function topicHelper(int $kanban_id): string
    {
        return self::KANBAN_TOPIC . $kanban_id;
    }
}
