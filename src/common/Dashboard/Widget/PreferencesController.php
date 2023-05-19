<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget;

use CSRFSynchronizerToken;
use HTTPRequest;
use PFUser;
use Feedback;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Widget\WidgetFactory;

class PreferencesController
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $disabled_project_widgets_checker;

    public function __construct(
        DashboardWidgetDao $dao,
        WidgetFactory $widget_factory,
        DisabledProjectWidgetsChecker $disabled_project_widgets_checker,
    ) {
        $this->dao                              = $dao;
        $this->widget_factory                   = $widget_factory;
        $this->disabled_project_widgets_checker = $disabled_project_widgets_checker;
    }

    public function display(HTTPRequest $request)
    {
        $user      = $request->getCurrentUser();
        $widget_id = $request->get('widget-id');

        $row = $this->dao->searchWidgetInDashboardById($widget_id)->getRow();

        $this->checkWidgetCanBeEdited($row, $user);
        $this->forceGroupIdToBePresentInRequest($request, $row);

        echo $this->getWidget($row)->getPreferences($row['id'], $row['content_id']);
    }

    public function update(HTTPRequest $request)
    {
        $user      = $request->getCurrentUser();
        $widget_id = $request->get('widget-id');

        $row = $this->dao->searchWidgetInDashboardById($widget_id)->getRow();

        $this->checkCSRF($row);

        $this->checkWidgetCanBeEdited($row, $user);
        $this->forceGroupIdToBePresentInRequest($request, $row);
        $this->forceContentIdToBePresentInRequest($request, $row);

        try {
            $widget = $this->getWidget($row);

            if ($this->disabled_project_widgets_checker->isWidgetDisabled($widget, $row['dashboard_type'])) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _('The widget is disabled in project dashboard.')
                );
            } else {
                $widget->updatePreferences($request);
            }
        } catch (\Exception $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _($exception->getMessage())
            );
        }

        $this->redirectToDashboard($row);
    }

    protected function checkWidgetCanBeEdited($row, PFUser $user)
    {
        if (! $row) {
            $GLOBALS['Response']->send400JSONErrors(_('We cannot find any edition information for the requested widget.'));
        }

        if ($row['dashboard_type'] === 'project' && ! $user->isAdmin($row['project_id'])) {
            $GLOBALS['Response']->send400JSONErrors(_('You must be a project admin to edit this widget.'));
        }

        if ($row['dashboard_type'] === 'user' && (int) $user->getId() !== (int) $row['user_id']) {
            $GLOBALS['Response']->send400JSONErrors(_('You can only edit your own widgets.'));
        }
    }

    protected function forceGroupIdToBePresentInRequest(HTTPRequest $request, array $row)
    {
        if ($row['dashboard_type'] === 'project') {
            $request->set('group_id', $row['project_id']);
        }
    }

    protected function forceContentIdToBePresentInRequest(HTTPRequest $request, array $row)
    {
        $request->set('content_id', $row['content_id']);
    }

    protected function getWidget(array $row): \Widget
    {
        $widget             = $this->widget_factory->getInstanceByWidgetName($row['name']);
        $widget->owner_type = $row['project_id'] ? 'g' : 'u';
        $widget->owner_id   = $row['project_id'] ? $row['project_id'] : $row['user_id'];
        $widget->loadContent($row['content_id']);

        return $widget;
    }

    private function checkCSRF(array $row)
    {
        if ($row['dashboard_type'] === 'project') {
            $csrf = new CSRFSynchronizerToken('/project/');
        } else {
            $csrf = new CSRFSynchronizerToken('/my/');
        }

        $csrf->check();
    }

    private function redirectToDashboard(array $row)
    {
        if ($row['dashboard_type'] === 'project') {
            $url = '/projects/' . $row['unix_group_name'] . '/';
        } else {
            $url = '/my/';
        }

        $GLOBALS['Response']->redirect(
            $url . '?' . http_build_query(
                [
                    'dashboard_id' => $row['dashboard_id'],
                ]
            )
        );
    }
}
