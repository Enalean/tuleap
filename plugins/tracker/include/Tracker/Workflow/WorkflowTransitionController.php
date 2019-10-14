<?php
/**
* Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use HTTPRequest;
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\CssAsset;

class WorkflowTransitionController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    private $tracker_factory;
    private $tracker_manager;

    /**
     * @var WorkflowMenuTabPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        TrackerFactory $tracker_factory,
        TrackerManager $tracker_manager,
        WorkflowMenuTabPresenterBuilder $presenter_builder
    ) {
        $this->tracker_factory    = $tracker_factory;
        $this->tracker_manager    = $tracker_manager;
        $this->presenter_builder  = $presenter_builder;
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
            $layout->redirect(TRACKER_BASE_URL . '/?tracker=' . urlencode((string) $tracker->getId()));
        }

        $javascriptAssets = new IncludeAssets(
            __DIR__ . '/../../../www/assets',
            TRACKER_BASE_URL . '/assets'
        );
        $layout->includeFooterJavascriptFile($javascriptAssets->getFileURL('tracker-workflow-transitions.js'));

        $cssAssets = new IncludeAssets(
            __DIR__ . '/../../../www/themes/BurningParrot/assets',
            TRACKER_BASE_URL . '/themes/BurningParrot/assets'
        );
        $layout->addCssAsset(new CssAsset($cssAssets, 'workflow'));

        $tracker->displayAdminItemHeaderBurningParrot(
            $this->tracker_manager,
            'editworkflow',
            dgettext('tuleap-tracker', 'Transition rules'),
            ['main_classes' => ['fluid-main']]
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR.'/workflow-transitions');
        $presenter = $this->presenter_builder->build($tracker, WorkflowMenuTabPresenterBuilder::TAB_TRANSITIONS);
        $renderer->renderToPage('workflow-transitions', $presenter);

        $tracker->displayFooter($this->tracker_manager);
    }
}
