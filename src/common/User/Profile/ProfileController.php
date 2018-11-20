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
 */

namespace Tuleap\User\Profile;

use ForgeConfig;
use HTTPRequest;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use UserHelper;
use UserManager;

class ProfileController implements DispatchableWithRequest
{
    /**
     * @var ProfilePresenterBuilder
     */
    private $presenter_builder;

    /**
     * ProfileController constructor.
     */
    public function __construct(ProfilePresenterBuilder $presenter_builder)
    {
        $this->presenter_builder = $presenter_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $user = UserManager::instance()->getUserByUserName($variables['name']);
        if ($user === null) {
            throw new NotFoundException(_("That user does not exist."));
        }

        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            $layout->redirect('/account/login.php');

            return;
        }

        if (ForgeConfig::get('display_deprecated_user_home')) {
            require_once('user_home.php');

            return;
        }


        $layout->header(['title' => UserHelper::instance()->getDisplayNameFromUser($user)]);
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
