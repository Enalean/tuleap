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

use CSRFSynchronizerToken;
use PFUser;
use Tuleap\Platform\Banner\Banner;
use Tuleap\Theme\BurningParrot\Navbar\MenuItem\LogoutPresenter;
use Tuleap\Theme\BurningParrot\Navbar\MenuItem\Presenter as MenuItemPresenter;
use URLRedirect;

class UserNavPresenter
{
    /** @var PFUser */
    private $current_user;

    /** @var bool */
    public $display_new_user_menu_item;
    /**
     * @var URLRedirect
     */
    private $url_redirect;
    /**
     * @var array
     */
    public $dashboards;
    /**
     * @var bool
     */
    public $has_one_dashboard;
    /**
     * @var bool
     */
    public $has_no_dashboards;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_platform_banner;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $platform_banner_is_visible;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $platform_banner_is_standard;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $platform_banner_is_warning;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $platform_banner_is_critical;

    public function __construct(
        PFUser $current_user,
        $display_new_user_menu_item,
        URLRedirect $url_redirect,
        array $dashboards,
        ?\Tuleap\Platform\Banner\BannerDisplay $platform_banner
    ) {
        $this->current_user               = $current_user;
        $this->display_new_user_menu_item = $display_new_user_menu_item;
        $this->url_redirect               = $url_redirect;
        $this->has_platform_banner        = $platform_banner !== null;
        $this->platform_banner_is_visible = $platform_banner && $platform_banner->isVisible();

        $this->platform_banner_is_standard = $platform_banner && $platform_banner->getImportance() === Banner::IMPORTANCE_STANDARD;
        $this->platform_banner_is_warning  = $platform_banner && $platform_banner->getImportance() === Banner::IMPORTANCE_WARNING;
        $this->platform_banner_is_critical = $platform_banner && $platform_banner->getImportance() === Banner::IMPORTANCE_CRITICAL;

        $this->dashboards          = $dashboards;
        $this->has_no_dashboards   = count($dashboards) === 0;
        $this->has_one_dashboard   = count($dashboards) === 1;
    }

    public function is_user_logged_in() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->current_user->isLoggedIn();
    }

    public function user_real_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->current_user->getRealName();
    }

    public function user_user_name() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->current_user->getUserName();
    }

    public function user_has_avatar() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->current_user->hasAvatar();
    }

    public function user_avatar_url() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->current_user->getAvatarUrl();
    }

    public function user_avatar_alt() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('User avatar');
    }

    public function my_account_label() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('menu', 'my_personal_page');
    }

    public function login_menu_item() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return new MenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'login'),
            $this->url_redirect->buildReturnToLogin($_SERVER),
            'fas fa-sign-in-alt',
            '',
            []
        );
    }

    public function new_user_menu_item() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return new MenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'new_user'),
            '/account/register.php',
            'fa fa-user-plus',
            '',
            []
        );
    }

    public function user_nav_items() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return [
            new MenuItemPresenter(
                $GLOBALS['Language']->getText('my_index', 'account_maintenance'),
                '/account/',
                'fa fa-cog',
                'only-icon',
                []
            )
        ];
    }

    public function logout_menu_item() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $logout_csrf = new CSRFSynchronizerToken('logout_action');
        return new LogoutPresenter(
            $GLOBALS['Language']->getText('include_menu', 'logout'),
            $logout_csrf
        );
    }
}
