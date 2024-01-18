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
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class MoveArtifactsAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private readonly TrackerFactory $tracker_factory,
        private readonly TrackerManager $tracker_manager,
        private readonly MoveActionAllowedDAO $move_action_allowed_dao,
    ) {
    }

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

        $tracker->displayAdminItemHeaderBurningParrot(
            $this->tracker_manager,
            'adminmoveartifacts',
            dgettext('tuleap-tracker', 'Move artifacts'),
        );

        $tracker_id = (int) $variables['tracker_id'];

        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/admin');
        $renderer->renderToPage(
            'admin-move-artifacts',
            new MoveArtifactsAdminPresenter(
                $tracker_id,
                new \CSRFSynchronizerToken(TRACKER_BASE_URL . "/move-artifacts/" . urlencode((string) $tracker_id)),
                $this->move_action_allowed_dao->isMoveActionAllowedInTracker($tracker_id),
            ),
        );

        $tracker->displayFooter($this->tracker_manager);
    }
}
