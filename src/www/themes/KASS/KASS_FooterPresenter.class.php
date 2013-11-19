<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class KASS_FooterPresenter {

    private $theme;

    function __construct($theme) {
        $this->theme = $theme;
    }

    public function isInDebugMode() {
        return (Config::get('DEBUG_MODE') && (Config::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')));
    }

    public function debugMode() {
        return $this->theme->showDebugInfo();
    }

    public function javascriptElements() {
        return $this->theme->displayFooterJavascriptElements();
    }

    public function footer() {
        global $Language;
        $version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
        ob_start();
        include($GLOBALS['Language']->getContent('layout/footer'));

        return ob_get_clean();
    }
}

?>