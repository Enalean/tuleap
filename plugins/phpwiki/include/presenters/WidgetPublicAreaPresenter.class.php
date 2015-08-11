<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class WidgetPublicAreaPresenter {

    private $url_service;
    private $url_logo;
    private $service_name;
    private $nb_pages;

    public function __construct($url_service, $url_logo, $service_name, $nb_pages) {
        $this->url_service  = $url_service;
        $this->url_logo     = $url_logo;
        $this->service_name = $service_name;
        $this->nb_pages     = $nb_pages;
    }

    public function url_service() {
        return $this->url_service;
    }

    public function url_logo() {
        return $this->url_logo;
    }

    public function service_name() {
        return $this->service_name;
    }

    public function nb_pages() {
        return $this->nb_pages;
    }

    public function page() {
        if ($this->nb_pages > 1) {
            return $GLOBALS['Language']->getText('plugin_phpwiki_widget', 'pages');
        } else {
            return $GLOBALS['Language']->getText('plugin_phpwiki_widget', 'page');
        }
    }
}