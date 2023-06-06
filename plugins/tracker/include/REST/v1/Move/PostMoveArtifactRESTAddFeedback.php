<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use Feedback;
use FeedbackDao;
use Tracker;
use Tuleap\Tracker\Artifact\Artifact;

final class PostMoveArtifactRESTAddFeedback implements AddPostMoveArtifactFeedback
{
    public function __construct(private readonly FeedbackDao $dao)
    {
    }

    public function addFeedback(Tracker $source_tracker, Tracker $target_tracker, Artifact $artifact, \PFUser $user): void
    {
        $tracker_source_name      = $source_tracker->getItemName();
        $tracker_destination_name = $target_tracker->getItemName();

        $message = sprintf(
            dgettext('tuleap-tracker', "%s has been successfully moved to %s"),
            $tracker_source_name . " #" . $artifact->getId(),
            $tracker_destination_name . " #" . $artifact->getId()
        );

        if ($source_tracker->getProject()->getID() != $target_tracker->getProject()->getID()) {
            $message = sprintf(
                dgettext('tuleap-tracker', "%s has been successfully moved to %s in %s"),
                $tracker_source_name . " #" . $artifact->getId(),
                $tracker_destination_name . " #" . $artifact->getId(),
                $target_tracker->getProject()->getPublicName()
            );
        }

        $feedback = new Feedback();
        $feedback->log(Feedback::INFO, $message);

        $this->dao->create($user->getSessionId(), $feedback->getLogs());
    }
}
