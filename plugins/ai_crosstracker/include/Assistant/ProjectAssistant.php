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
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\RetrieveMultipleTrackers;

final readonly class ProjectAssistant implements Assistant
{
    public function __construct(
        private ProjectByIDFactory $project_factory,
        private RetrieveMultipleTrackers $tracker_factory,
        private RetrieveUsedFields $fields_factory,
        private ProjectCrossTrackerWidget $widget,
    ) {
    }

    /**
     * @param Message[] $messages
     * @throws RestException
     */
    #[Override]
    public function getCompletion(\PFUser $user, array $messages): Completion
    {
        $project = $this->project_factory->getProjectById($this->widget->getProjectId());

        $trackers = [];
        foreach ($this->tracker_factory->getTrackersByGroupId((int) $project->getID()) as $tracker) {
            if (! $tracker->isActive() || ! $tracker->userCanView($user)) {
                continue;
            }

            $tracker_description = [
                'tracker_name' => $tracker->getItemName(),
                'tracker_label' => $tracker->getName(),
                'fields' => [],
            ];

            $fields = $this->fields_factory->getUsedFields($tracker);
            foreach ($fields as $field) {
                if (! $field->isUsed() || ! $field->userCanRead($user)) {
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
            Model::DEVSTRALL_2512,
            AssistantResponseFormatBuilder::buildFormat(),
            new Message(
                Role::SYSTEM,
                new ChunkContent(
                    new TextChunk(
                        <<<EOT
                        ### Assistant goal

                        You are an assistant that helps to generate TQL queries for users. TQL is a pseudo programming
                        language, described in section "TQL documentation" section below.

                        Stick to documentation provided below, there is no other functions or language keywords than
                        what is covered in documentation.

                        You do not provide assistance for anything that does not aim to produce a TQL query. Users
                        request information using only plaintext.
                        EOT
                    ),
                    new TextChunk('### TQL documentation' . PHP_EOL . PHP_EOL . $tql_doc),
                    new TextChunk(<<<EOT
                        ### Tips and tricks

                        In addition to this documentation, here is a list of tips from errors you are commonly doing:
                        * there is no `AS` keyword for `SELECT` part, only fields name must be used.
                        * ORDER BY must have a direction
                        * There is no JOIN
                        * There is no CLOSED() function
                        * There is no LIKE keyword for string comparison, only = or !=
                        * fields can be listed in `SELECT` part even if they are not part of all trackers
                        EOT),
                    new TextChunk(
                        <<<EOT
                            ### Custom fields

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

                            User refer to artifact also by naming them "ticket" or "tickets".
                            EOT
                    ),
                    new TextChunk('### Available trackers' . PHP_EOL . PHP_EOL . $json_encoded_trackers),
                ),
            ),
            ... $messages
        );
    }
}
