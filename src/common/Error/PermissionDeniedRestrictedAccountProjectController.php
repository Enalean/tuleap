<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Error;

use ForgeConfig;
use Project;
use TemplateRendererFactory;
use ThemeManager;
use Tuleap\User\CurrentUserWithLoggedInInformation;

class PermissionDeniedRestrictedAccountProjectController
{
    /**
     * @var ThemeManager
     */
    private $theme_manager;
    /**
     * @var ErrorDependenciesInjector
     */
    private $dependencies_injector;
    /**
     * @var PlaceHolderBuilder
     */
    private $place_holder_builder;

    public function __construct(
        ThemeManager $theme_manager,
        ErrorDependenciesInjector $dependencies_injector,
        PlaceHolderBuilder $place_holder_builder,
    ) {
        $this->theme_manager         = $theme_manager;
        $this->dependencies_injector = $dependencies_injector;
        $this->place_holder_builder  = $place_holder_builder;
    }

    public function displayError(CurrentUserWithLoggedInInformation $user, Project $project)
    {
        $layout = $this->theme_manager->getBurningParrot($user);
        if ($layout === null) {
            throw new \Exception("Could not load BurningParrot theme");
        }

        $layout->header(\Tuleap\Layout\HeaderConfiguration::fromTitle(_("Project access error")));

        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/error/'
        );

        $this->dependencies_injector->includeJavascriptDependencies($layout);

        $placeholder = $this->place_holder_builder->buildPlaceHolder($project);

        $renderer->renderToPage(
            'permission-denied-restricted-account-project',
            new ProjectPermissionDeniedPresenter($project, $this->getToken(), $placeholder, "/join-project-restricted-user-mail/")
        );

        $layout->footer([]);
    }

    /**
     * @return \CSRFSynchronizerToken
     */
    private function getToken()
    {
        return new \CSRFSynchronizerToken("/join-project-restricted-user-mail/");
    }
}
