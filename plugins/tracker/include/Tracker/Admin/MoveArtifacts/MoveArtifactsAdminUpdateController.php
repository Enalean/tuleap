<?php
/**
* Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\MoveArtifacts;

use HTTPRequest;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class MoveArtifactsAdminUpdateController implements DispatchableWithRequest
{
    public function __construct(
        private readonly TrackerFactory $tracker_factory,
        private readonly MoveActionAllowedDAO $move_action_allowed_dao,
    ) {
    }

    /**
     * @psalm-return never-return
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['tracker_id']);
        if (! $tracker) {
            throw new NotFoundException(dgettext('tuleap-tracker', "Tracker does not exist"));
        }

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.')
            );
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()));
        }

        $tracker_id = $tracker->getId();
        (new \CSRFSynchronizerToken(TRACKER_BASE_URL . "/move-artifacts/" . urlencode((string) $tracker_id)))->check();

        $enable_move = $request->get('enable-move-artifacts');
        if ($enable_move) {
            $this->move_action_allowed_dao->enableMoveArtifactInTracker($tracker_id);

            $layout->addFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-tracker', 'Move of artifacts successfully enabled in tracker.'),
            );
        } else {
            $this->move_action_allowed_dao->forbidMoveArtifactInTracker($tracker_id);

            $layout->addFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-tracker', 'Move of artifacts successfully forbidden in tracker.'),
            );
        }

        $layout->redirect(TRACKER_BASE_URL . '/move-artifacts/' . urlencode((string) $tracker->getId()));
    }
}
