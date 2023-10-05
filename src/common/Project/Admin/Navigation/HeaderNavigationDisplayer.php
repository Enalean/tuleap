<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Project\Admin\Navigation;

use EventManager;
use ForgeConfig;
use HTTPRequest;
use Project;
use Tuleap\Layout\HeaderConfigurationBuilder;

class HeaderNavigationDisplayer
{
    public function displayBurningParrotNavigation($title, Project $project, $current_pane_shortname)
    {
        $this->displayNavigation($title, $project, "navigation", $current_pane_shortname);
    }

    public function displayFlamingParrotNavigation($title, Project $project, $current_pane_shortname)
    {
        $this->displayNavigation($title, $project, "navigation_flaming_parrot", $current_pane_shortname);
    }

    private function displayNavigation($title, Project $project, $template_name, $current_pane_shortname)
    {
        $params = HeaderConfigurationBuilder::get($title . ' - ' . $project->getPublicName())
            ->inProject($project, \Service::ADMIN)
            ->withBodyClass(['project-administration'])
            ->build();

        site_project_header($project, $params);

        $template_path = ForgeConfig::get('tuleap_dir') . '/src/templates/project';

        $request = HTTPRequest::instance();

        $builder  = new NavigationPresenterBuilder(
            new NavigationPermissionsDropdownPresenterBuilder(),
            EventManager::instance()
        );
        $renderer = \TemplateRendererFactory::build()->getRenderer($template_path);

        $navigation_presenter = $builder->build($project, $request, $current_pane_shortname);

        $renderer->renderToPage($template_name, $navigation_presenter);
        $renderer->renderToPage('start-project-admin-content', []);
    }
}
