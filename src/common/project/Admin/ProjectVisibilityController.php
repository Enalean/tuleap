<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuealp\project\Admin;

use ForgeConfig;
use HTTPRequest;
use Project;
use TemplateRendererFactory;

class ProjectVisibilityController
{
    public function display(HTTPRequest $request)
    {
        $project = $request->getProject();
        $this->displayHeader($project);
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/project/'
        );

        $presenter = new ProjectGlobalVisibilityPresenter();
        echo $renderer->renderToString('project-visibility-form', $presenter);
    }

    private function displayHeader(Project $project)
    {
        project_admin_header(
            array(
                'title' => $GLOBALS['Language']->getText('project_admin_editgroupinfo', 'editing_g_info'),
                'group' => $project->getGroupId(),
                'help'  => 'project-admin.html#project-public-information'
            )
        );
    }
}
