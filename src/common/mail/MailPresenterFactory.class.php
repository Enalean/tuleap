<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MailPresenterFactory {

    const FLAMING_PARROT_THEME = 'FlamingParrot';

    /**
     * Create a presenter for email.
     *
     * @return MailRegisterPresenter
     */
    public function createPresenter($login, $password, $confirm_hash, $presenter_role) {
        $base_url       = get_server_url();
        $defaultTheme   = ForgeConfig::get('sys_themedefault');
        $color_logo     = "#0000";
        $color_button   = "#347DBA";

        if ($this::themeIsFlamingParrot($defaultTheme)) {
            $defaultThemeVariant = ForgeConfig::get('sys_default_theme_variant');
            $color_logo          = FlamingParrot_Theme::getColorOfCurrentTheme($defaultThemeVariant);
            $color_button        = $color_logo;
        }

        $logo_url   = $base_url."/themes/".$defaultTheme."/images/organization_logo.png";
        $has_logo   = file_exists(dirname(__FILE__) . '/../../www/themes/'.$defaultTheme.'/images/organization_logo.png');

        $attributes_presenter = array(
            "login"         => $login,
            "password"      => $password,
            "color_logo"    => $color_logo,
            "color_button"  => $color_button,
            "confirm_hash"  => $confirm_hash,
            "base_url"      => $base_url,
            "has_logo"      => $has_logo,
            "logo_url"      => $logo_url
        );

        if ($presenter_role == "user") {
            $presenter = $this->createUserEmailPresenter($attributes_presenter);
        } else {
            $presenter = $this->createAdminEmailPresenter($attributes_presenter);
        }
        return $presenter;
    }

    /**
     * Check if we need to display the theme color.
     *
     * @return boolean
     */
    private function themeIsFlamingParrot($theme) {
        return $theme === self::FLAMING_PARROT_THEME;
    }

    /**
     * Create a presenter for admin
     * account register.
     *
     * @return MailRegisterByAdminPresenter
     */
    private function createAdminEmailPresenter(Array $attributes_presenter) {
        $login      = $attributes_presenter["login"];
        $password   = $attributes_presenter["password"];

        include($GLOBALS['Language']->getContent('account/new_account_email'));
        $presenter = new MailRegisterByAdminPresenter(
            $attributes_presenter["has_logo"],
            $attributes_presenter["logo_url"],
            $title,
            $section_one,
            $section_two,
            $section_after_login,
            $thanks,
            $signature,
            $help,
            $attributes_presenter["color_logo"],
            $login,
            $section_three,
            $section_after_password,
            $password
        );

        return $presenter;
    }

    /**
     * Create a presenter for user
     * account register.
     *
     * @return MailRegisterByUserPresenter
     */
    private function createUserEmailPresenter(Array $attributes_presenter) {
        $base_url       = $attributes_presenter["base_url"];
        $login          = $attributes_presenter["login"];
        $confirm_hash   = $attributes_presenter["confirm_hash"];

        include($GLOBALS['Language']->getContent('include/new_user_email'));
        $redirect_url = $base_url ."/account/verify.php?confirm_hash=$confirm_hash";

        $presenter = new MailRegisterByUserPresenter(
             $attributes_presenter["has_logo"],
             $attributes_presenter["logo_url"],
             $title,
             $section_one,
             $section_two,
             $section_after_login,
             $thanks,
             $signature,
             $help,
             $attributes_presenter["color_logo"],
             $login,
             $redirect_url,
             $redirect_button,
             $attributes_presenter["color_button"]
        );
        return $presenter;
    }
}