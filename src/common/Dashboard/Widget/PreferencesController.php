<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use HTTPRequest;
use PFUser;

class PreferencesController
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    public function __construct(DashboardWidgetDao $dao)
    {
        $this->dao = $dao;
    }

    public function display(HTTPRequest $request)
    {
        $user      = $request->getCurrentUser();
        $widget_id = $request->get('widget-id');

        $row = $this->dao->searchWidgetInDashboardById($widget_id)->getRow();

        $this->checkWidgetCanBeEdited($row, $user);
        $this->forceGroupIdToBePresentInRequest($request, $row);

        echo $this->getWidget($row)->getPreferencesForBurningParrot($row['id']);
    }

    protected function checkWidgetCanBeEdited($row, PFUser $user)
    {
        if (! $row) {
            $GLOBALS['Response']->send400JSONErrors(_('We cannot find any edition information for the requested widget.'));
        }

        if ($row['dashboard_type'] === 'project' && ! $user->isAdmin($row['project_id'])) {
            $GLOBALS['Response']->send400JSONErrors(_('You must be a project admin to edit this widget.'));
        }

        if ($row['dashboard_type'] === 'user' && (int)$user->getId() !== (int)$row['user_id']) {
            $GLOBALS['Response']->send400JSONErrors(_('You can only edit your own widgets.'));
        }
    }

    protected function forceGroupIdToBePresentInRequest(HTTPRequest $request, array $row)
    {
        if ($row['dashboard_type'] === 'project') {
            $request->set('group_id', $row['project_id']);
        }
    }

    protected function getWidget(array $row)
    {
        $widget             = \Widget::getInstance($row['name']);
        $widget->owner_type = $row['project_id'] ? 'g' : 'u';
        $widget->owner_id   = $row['project_id'] ? $row['project_id'] : $row['user_id'];
        $widget->loadContent($row['content_id']);

        return $widget;
    }
}
