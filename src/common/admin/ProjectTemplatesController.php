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
 *
 */

namespace Tuleap\Admin;

use ForgeConfig;
use HTTPRequest;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\TemplateListPresenter;
use Tuleap\Project\Admin\TemplatePresenter;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ProjectTemplatesController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $templates_presenters = array();
        foreach (ProjectManager::instance()->getSiteTemplates() as $template) {
            $templates_presenters[] = new TemplatePresenter($template);
        }

        $title = _('Project templates');
        $presenter = new TemplateListPresenter(new ProjectCreationNavBarPresenter('templates'), $title, $templates_presenters);

        $admin_page = new AdminPageRenderer();
        $admin_page->renderANoFramedPresenter(
            $title,
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
            'templatelist',
            $presenter
        );
    }
}
