<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tracker_ArtifactFactory;
use Tuleap\Queue\WorkerEvent;

class AsynchronousActionsRunner
{
    public const TOPIC = 'tuleap.tracker.artifact';

    /**
     * @var ActionsRunner
     */
    private $actions_runner;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    public function __construct(ActionsRunner $actions_runner, Tracker_ArtifactFactory $tracker_artifact_factory)
    {
        $this->actions_runner           = $actions_runner;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
    }

    public static function addListener(WorkerEvent $event)
    {
        if ($event->getEventName() !== self::TOPIC) {
            return;
        }

        $async_runner = new self(ActionsRunner::build($event->getLogger()), Tracker_ArtifactFactory::instance());
        $async_runner->process($event);
    }

    public function process(WorkerEvent $event)
    {
        $message = $event->getPayload();

        if (! isset($message['artifact_id'], $message['changeset_id'])) {
            $event_name = $event->getEventName();
            $event->getLogger()->warning("The payload for $event_name seems to be malformed, ignoring");
            $event->getLogger()->debug("Malformed payload for $event_name: " . var_export($event->getPayload(), true));
            return;
        }

        $artifact = $this->tracker_artifact_factory->getArtifactById($message['artifact_id']);
        if ($artifact === null) {
            $event->getLogger()->info(
                'Not able to process an event ' . $event->getEventName() . ', the artifact #' . $message['artifact_id'] . ' ' .
                'can not be found. The artifact might have been deleted.'
            );
            return;
        }
        $changeset = $artifact->getChangeset($message['changeset_id']);
        if ($changeset === null) {
            $event->getLogger()->info(
                'Not able to process an event ' . $event->getEventName() . ', the changeset #' . $message['changeset_id'] . ' ' .
                'can not be found.'
            );
            return;
        }

        $this->actions_runner->processAsyncPostCreationActions($changeset);
    }
}
