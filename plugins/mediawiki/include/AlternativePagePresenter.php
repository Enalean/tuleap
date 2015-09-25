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

class Mediawiki_AlternativePagePresenter {

    /** @var array */
    private $languages;

    /** @var Boolean */
    private $user_is_administrator;

    /** @var String */
    private $language_admin_url;

    public function __construct(array $languages, $user_is_administrator, $language_admin_url) {
        $this->languages             = $languages;
        $this->user_is_administrator = $user_is_administrator;
        $this->language_admin_url    = $language_admin_url;
    }

    public function available_welcome_pages() {
        return $this->languages;
    }

    public function is_administrator() {
        return $this->user_is_administrator;
    }

    public function admin_warning_message() {
        return $GLOBALS['Language']->getText(
            'plugin_mediawiki',
            'language_not_set_admin_warning',
            array($this->language_admin_url)
        );
    }

    public function warning_message() {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'language_not_set_warning');
    }

    public function intro_text() {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'language_not_set_intro');
    }
}
