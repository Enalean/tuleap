<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar;

use EventManager;
use PFUser;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\layout\NewDropdown\NewDropdownPresenter;
use Tuleap\User\Account\RegistrationGuardEvent;
use Tuleap\Widget\WidgetFactory;
use URLRedirect;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class PresenterBuilder
{
    /** @var PFUser */
    private $current_user;

    public function build(
        PFUser $current_user,
        URLRedirect $url_redirect,
        NewDropdownPresenter $new_dropdown_presenter,
        bool $should_logo_be_displayed,
        bool $is_legacy_logo_customized,
        bool $is_svg_logo_customized,
        ?\Tuleap\Platform\Banner\BannerDisplay $platform_banner
    ) {
        $this->current_user    = $current_user;

        $event_manager = EventManager::instance();

        $widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $event_manager
        );
        $user_dashboard_retriever = new UserDashboardRetriever(new UserDashboardDao(new DashboardWidgetDao($widget_factory)));

        return new Presenter(
            new UserNavPresenter(
                $this->current_user,
                $this->displayNewAccountMenuItem(),
                $url_redirect,
                $user_dashboard_retriever->getAllUserDashboards($this->current_user),
                $platform_banner,
            ),
            $new_dropdown_presenter,
            $current_user->isSuperUser(),
            $should_logo_be_displayed,
            $is_legacy_logo_customized,
            $is_svg_logo_customized,
            (new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($current_user),
        );
    }

    private function displayNewAccountMenuItem()
    {
        $registration_guard = EventManager::instance()->dispatch(new RegistrationGuardEvent());
        assert($registration_guard instanceof RegistrationGuardEvent);
        return $registration_guard->isRegistrationPossible();
    }
}
