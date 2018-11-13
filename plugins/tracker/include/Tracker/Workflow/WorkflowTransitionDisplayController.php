<?php
/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
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
namespace Tuleap\Tracker\Workflow;

use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use TrackerFactory;
use TrackerManager;
use Tuleap\Request\NotFoundException;

class WorkflowTransitionDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    private $tracker_factory;
    private $tracker_manager;

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager
    ) {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_manager = $tracker_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['tracker_id']);
        if (! $tracker) {
            throw new NotFoundException(dgettext('tuleap-tracker', "Tracker does not exist"));
        }

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            $layout->addFeedback(\Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }

        $tracker->displayAdminItemHeader($this->tracker_manager, 'editworkflow');

        $tracker->displayFooter($this->tracker_manager);
    }
}
