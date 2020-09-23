<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\layout\NewDropdown\NewDropdownPresenter;

class FlamingParrot_NavBarPresenter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $history;

    private $imgroot;

    /** @var PFUser */
    private $user;

    private $display_new_account;

    /** @var string */
    public $motd;

    /** @var bool */
    public $has_motd;

    /**
     * @var CSRFSynchronizerToken
     */
    public $logout_csrf;

    /**
     * @var URLRedirect
     */
    private $url_redirect;

    /**
     * @var string
     */
    public $logout_label;

    /**
     * @var string
     */
    public $my_account_label;

    /**
     * @var array
     */
    public $dashboards;
    /**
     * @var bool
     */
    public $has_no_dashboards;
    /**
     * @var bool
     */
    public $has_one_dashboard;
    /**
     * @var NewDropdownPresenter
     */
    public $new_dropdown;
    /**
     * @var \Tuleap\User\SwitchToPresenter|null
     * @psalm-readonly
     */
    public $switch_to;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_super_user;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $should_logo_be_displayed;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_legacy_logo_customized;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_svg_logo_customized;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $can_buddies_be_invited;

    public function __construct(
        $imgroot,
        PFUser $user,
        $display_new_account,
        $motd,
        CSRFSynchronizerToken $logout_csrf,
        URLRedirect $url_redirect,
        array $dashboards,
        NewDropdownPresenter $new_dropdown,
        $should_logo_be_displayed,
        ?\Tuleap\User\SwitchToPresenter $switch_to,
        bool $is_legacy_logo_customized,
        bool $is_svg_logo_customized,
        bool $can_buddies_be_invited
    ) {
        $this->imgroot                   = $imgroot;
        $this->user                      = $user;
        $this->display_new_account       = $display_new_account;
        $this->motd                      = $motd;
        $this->has_motd                  = ! empty($motd);
        $this->logout_csrf               = $logout_csrf;
        $this->url_redirect              = $url_redirect;
        $this->dashboards                = $dashboards;
        $this->has_no_dashboards         = count($dashboards) === 0;
        $this->has_one_dashboard         = count($dashboards) === 1;
        $this->new_dropdown              = $new_dropdown;
        $this->switch_to                 = $switch_to;
        $this->should_logo_be_displayed  = $should_logo_be_displayed;
        $this->is_legacy_logo_customized = $is_legacy_logo_customized;
        $this->is_svg_logo_customized    = $is_svg_logo_customized;
        $this->can_buddies_be_invited    = $can_buddies_be_invited;
        $this->is_super_user             = $user->isSuperUser();

        $this->logout_label         = $GLOBALS['Language']->getText('include_menu', 'logout');
        $this->my_account_label     = $GLOBALS['Language']->getText('my_index', 'account_maintenance');

        $this->current_user_id = $user->getId();
        $this->history         = _('History');
        $this->clear_history   = _('Clear history');
        $this->empty_history   = _('Your history is empty');
        $this->error_fetch     = _('An error occurred while fetching the content of your history');
        $this->error_clear     = _('An error occurred while clearing the content of your history');
    }

    public function imgroot()
    {
        return $this->imgroot;
    }

    public function user_is_logged_in() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->user->isLoggedIn();
    }

    public function user_real_name() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->user->getRealName();
    }

    public function user_login_name() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return "@" . $this->user->getUnixName();
    }

    public function has_user_avatar() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->user->hasAvatar();
    }

    public function user_avatar() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->user->getAvatarUrl();
    }

    public function display_new_user() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->display_new_account;
    }

    public function account_options_text() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('account_options', 'preferences');
    }

    public function menu_home_text() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('menu', 'home');
    }

    public function include_menu_login_text() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('include_menu', 'login');
    }

    public function include_menu_new_user_text() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('include_menu', 'new_user');
    }

    public function login_url() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->url_redirect->buildReturnToLogin($_SERVER);
    }
}
