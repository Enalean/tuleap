<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use Tuleap\Tracker\Admin\MoveArtifacts\MoveActionAllowedDAO;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\PromotedTrackerDao;

class TrackerCreationSettingsBuilder
{
    public function __construct(
        private readonly PromotedTrackerDao $in_new_dropdown_dao,
        private readonly TrackerPrivateCommentUGroupEnabledDao $private_comment_dao,
        private readonly MoveActionAllowedDAO $move_action_allowed_dao,
    ) {
    }

    public function build(\Tracker $tracker): TrackerCreationSettings
    {
        $is_displayed_in_new_dropdown = false;
        if ($this->in_new_dropdown_dao->isContaining($tracker->getId())) {
            $is_displayed_in_new_dropdown = true;
        }
        $is_used_private_comment   = $this->private_comment_dao->isTrackerEnabledPrivateComment($tracker->getId());
        $is_move_artifacts_allowed = $this->move_action_allowed_dao->isMoveActionAllowedInTracker($tracker->getId());

        return new TrackerCreationSettings($is_displayed_in_new_dropdown, $is_used_private_comment, $is_move_artifacts_allowed);
    }
}
