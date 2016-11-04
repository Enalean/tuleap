<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use HTTPRequest;
use PFUser;
use CSRFSynchronizerToken;

class UserNavPresenter
{
    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $current_user;

    /** @var boolean */
    public $display_new_user_menu_item;

    public function __construct(
        HTTPRequest $request,
        PFUser $current_user,
        $display_new_user_menu_item
    ) {
        $this->request                    = $request;
        $this->current_user               = $current_user;
        $this->display_new_user_menu_item = $display_new_user_menu_item;
    }

    public function is_user_logged_in()
    {
        return $this->current_user->isLoggedIn();
    }

    private function isUserTryingToLogin($request_uri)
    {
        return strpos($request_uri, '/account/login.php') === 0;
    }

    public function user_real_name()
    {
        return $this->current_user->getRealName();
    }

    public function user_user_name()
    {
        return $this->current_user->getUserName();
    }

    public function user_has_avatar()
    {
        return $this->current_user->hasAvatar();
    }

    public function user_avatar_url()
    {
        return $this->current_user->getAvatarUrl();
    }

    public function my_account_label()
    {
        return $GLOBALS['Language']->getText('my_index', 'account_maintenance');
    }

    public function login_menu_item()
    {
        $return_to = '';

        if ($this->isUserTryingToLogin($_SERVER['REQUEST_URI'])) {
            $return_to = '?return_to=' . urlencode($this->request->get('return_to'));
        }

        return new GlobalMenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'login'),
            '/account/login.php' . $return_to,
            '',
            ''
        );
    }

    public function new_user_menu_item()
    {
        return new GlobalMenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'new_user'),
            '/account/register.php',
            '',
            ''
        );
    }

    public function user_nav_items()
    {
        return array(
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('menu', 'my_personal_page'),
                '/my/',
                'fa fa-home',
                'only-icon'
            )
        );
    }

    public function logout_menu_item()
    {
        $logout_csrf = new CSRFSynchronizerToken('logout_action');
        return new GlobalLogoutMenuItemPresenter(
            $GLOBALS['Language']->getText('include_menu', 'logout'),
            $logout_csrf
        );
    }
}
