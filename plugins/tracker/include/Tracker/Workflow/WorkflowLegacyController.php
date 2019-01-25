<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Feedback;
use HTTPRequest;
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\CssAsset;
use Workflow_Dao;

class WorkflowLegacyController implements DispatchableWithRequest
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Workflow_Dao
     */
    private $workflow_dao;

    public function __construct(TrackerFactory $tracker_factory, Workflow_Dao $workflow_dao)
    {
        $this->tracker_factory = $tracker_factory;
        $this->workflow_dao    = $workflow_dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['tracker_id']);
        if (! $tracker) {
            throw new NotFoundException(dgettext('tuleap-tracker', "Tracker does not exist"));
        }

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()));
        }

        $admin_transition_url = TRACKER_BASE_URL . '/?tracker=' . urlencode($tracker->getId()) . "&func=admin-workflow-transitions";

        $deactivate_legacy_transitions = $request->get('deactivate_legacy_transitions');
        if ($deactivate_legacy_transitions === false) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The request is not valid.')
            );
            $layout->redirect($admin_transition_url);
        }

        $workflow = $tracker->getWorkflow();

        if ($workflow->is_used || ! $workflow->isLegacy()) {
            $layout->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-tracker', 'This workflow is already in a functional state. Skipping.')
            );
            $layout->redirect($admin_transition_url);
        }

        $this->workflow_dao->removeWorkflowLegacyState($workflow->getId());

        $layout->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-tracker', 'Transitions are now fully deactivated.')
        );
        $layout->redirect($admin_transition_url);
    }
}
