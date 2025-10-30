<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AICrossTracker\Assistant;

use Luracast\Restler\RestException;
use Override;
use Tuleap\AI\Mistral\ChunkContent;
use Tuleap\AI\Mistral\Completion;
use Tuleap\AI\Mistral\Message;
use Tuleap\AI\Mistral\Model;
use Tuleap\AI\Mistral\Role;
use Tuleap\AI\Mistral\TextChunk;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;

final readonly class ProjectAssistant implements Assistant
{
    public function __construct(private ProjectCrossTrackerWidget $widget)
    {
    }

    /**
     * @param Message[] $messages
     * @throws RestException
     */
    #[Override]
    public function getCompletion(\PFUser $user, array $messages): Completion
    {
        $project = \ProjectManager::instance()->getProjectById($this->widget->getProjectId());

        $trackers = [];
        foreach (\TrackerFactory::instance()->getTrackersByGroupId((int) $project->getID()) as $tracker) {
            if (! $tracker->isActive() || ! $tracker->userCanView($user)) {
                continue;
            }

            $tracker_description = [
                'tracker_name' => $tracker->getItemName(),
                'tracker_label' => $tracker->getName(),
                'fields' => [],
            ];

            $fields = $tracker->getFormElementFields();
            foreach ($fields as $field) {
                if (! $field->isUsed() || $field->userCanRead($user)) {
                    continue;
                }
                $tracker_description['fields'][] = [
                    'field_name' => $field->getName(),
                    'field_label' => $field->getLabel(),
                ];
            }

            $trackers[] = $tracker_description;
        }
        $json_encoded_trackers = json_encode($trackers);
        assert(is_string($json_encoded_trackers));

        $tql_doc = file_get_contents(__DIR__ . '/tql.html');
        if ($tql_doc === false) {
            throw new RestException(500, 'TQL doc error');
        }

        return new Completion(
            Model::MEDIUM_2508,
            new Message(
                Role::SYSTEM,
                new ChunkContent(
                    new TextChunk(
                        <<<EOT
                            You are an assistant that helps to generate TQL queries for users. TQL is a pseudo programming
                            language, described in section "TQL documentation" below.
                            EOT
                    ),
                    new TextChunk('### TQL documentation' . PHP_EOL . $tql_doc),
                    new TextChunk(
                        <<<EOT
                            The requester is in the context of a Tuleap project, queries will usually have `FROM @project = 'self'`
                            to refer to the current project.

                            In "Available trackers" section below you will find the json encoded structure of the trackers.
                            This structure list all trackers of the current project (corresponds to @project = 'self' in TQL).
                            Each tracker comes with:
                            * `item_name`, the technical name of the tracker
                            * `label`, the user friendly name of the tracker
                            * `fields`, the collection of fields
                            Each field comes with:
                            * `field_name`, the technical name of the field
                            * `field_label`, the user friendly name of the field

                            User will refer in their prompt to tracker by their `tracker_name` or by their `tracker_label`.
                            The TQL query will always use the `tracker_name`.
                            User will refer in their prompt to fields by their `field_name` or `field_label`. The TQL query
                            will always use the `field_name`.
                            EOT
                    ),
                    new TextChunk('### Available trackers' . PHP_EOL . $json_encoded_trackers),
                ),
            ),
            ... $messages
        );
    }
}
