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

namespace Tuleap\User\Profile;

use ForgeConfig;
use HTTPRequest;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use UserHelper;
use UserManager;

class ProfileController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var ProfilePresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var ProfileAsJSONForTooltipController
     */
    private $json_controller;

    public function __construct(
        ProfilePresenterBuilder $presenter_builder,
        ProfileAsJSONForTooltipController $json_controller,
    ) {
        $this->presenter_builder = $presenter_builder;
        $this->json_controller   = $json_controller;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = UserManager::instance()->getUserByUserName($variables['name']);
        if ($user === null) {
            throw new NotFoundException(_("That user does not exist."));
        }

        $current_user = $request->getCurrentUser();

        if ($request->get('as-json-for-tooltip')) {
            $this->json_controller->process($current_user, $user);

            return;
        }

        if ($current_user->isAnonymous()) {
            $layout->redirect('/account/login.php');
        }

        $layout->header(
            HeaderConfigurationBuilder::get(UserHelper::instance()->getDisplayNameFromUser($user))
                ->withBodyClass(['body-user-profile'])
                ->build()
        );
        $this->renderToPage($user, $current_user);
        $layout->footer([]);
    }

    /**
     * @param $user
     * @param $current_user
     */
    private function renderToPage(PFUser $user, PFUser $current_user)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/user'
        );
        $renderer->renderToPage('profile', $this->presenter_builder->getPresenter($user, $current_user));
    }
}
