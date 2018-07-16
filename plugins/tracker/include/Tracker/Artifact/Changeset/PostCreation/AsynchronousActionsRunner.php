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
    const TOPIC = 'tuleap.tracker.artifact';

    public function addListener(WorkerEvent $event)
    {
        if ($event->getEventName() === self::TOPIC) {
            $message = $event->getPayload();
            $notifier = ActionsRunner::build($event->getLogger());
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($message['artifact_id']);
            $changeset = $artifact->getChangeset($message['changeset_id']);

            $notifier->processAsyncPostCreationActions($changeset);
        }
    }
}
