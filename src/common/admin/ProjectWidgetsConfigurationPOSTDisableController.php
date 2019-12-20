<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Admin;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Widget\WidgetFactory;

class ProjectWidgetsConfigurationPOSTDisableController implements DispatchableWithRequest
{
    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var DisabledProjectWidgetsDao
     */
    private $disabled_project_widgets_dao;

    public function __construct(WidgetFactory $widget_factory, DisabledProjectWidgetsDao $disabled_project_widgets_dao)
    {
        $this->widget_factory               = $widget_factory;
        $this->disabled_project_widgets_dao = $disabled_project_widgets_dao;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $csrf_token = new CSRFSynchronizerToken('/admin/project-creation/widgets');
        $csrf_token->check();

        $widget_name = (string) $variables['widget-id'];
        $widget = $this->widget_factory->getInstanceByWidgetName($widget_name);

        if ($widget === null) {
            throw new NotFoundException(_('Widget not found.'));
        }

        $this->disabled_project_widgets_dao->disableWidget($widget_name);

        $layout->addFeedback(
            \Feedback::INFO,
            _('Widget successfully disabled.')
        );

        $layout->redirect('/admin/project-creation/widgets');
    }
}
