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
use TemplateRendererFactory;
use ThemeManager;
use Tuleap\User\CurrentUserWithLoggedInInformation;

class PermissionDeniedRestrictedAccountController
{
    /**
     * @var ThemeManager
     */
    private $theme_manager;

    public function __construct(ThemeManager $theme_manager)
    {
        $this->theme_manager = $theme_manager;
    }

    public function displayError(CurrentUserWithLoggedInInformation $user)
    {
        $layout = $this->theme_manager->getBurningParrot($user);
        if ($layout === null) {
            throw new \Exception("Could not load BurningParrot theme");
        }

        $layout->header(\Tuleap\Layout\HeaderConfiguration::fromTitle(_("Permission denied")));

        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/error/'
        );

        $renderer->renderToPage('permission-denied-restricted-account', []);

        $layout->footer([]);
    }
}
