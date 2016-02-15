<?php
/**
 * Copyright (c) Enalean, 2013-2016. All Rights Reserved.
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

class User_LoginPresenterBuilder {

    /** @return User_LoginPresenter */
    public function build($return_to, $printer_version, $form_loginname) {
        $additional_connectors = '';

        EventManager::instance()->processEvent(
            Event::LOGIN_ADDITIONAL_CONNECTOR,
            array(
                'return_to'            => $return_to,
                'additional_connector' => &$additional_connectors
            )
        );

        $presenter = new User_LoginPresenter(
            $return_to,
            $printer_version,
            $form_loginname,
            $this->getToggleSSL(),
            $additional_connectors
        );

        $authoritative = false;

        EventManager::instance()->processEvent(
            'login_presenter',
            array(
                'presenter'     => &$presenter,
                'authoritative' => &$authoritative,
            )
        );

        return $presenter;
    }

    /** @return User_LoginPresenter */
    public function buildForHomepage() {
        $return_to       = '';
        $printer_version = 0;
        $form_loginname  = '';

        return $this->build($return_to, $printer_version, $form_loginname);
    }

    /**
     * Only show the stay in SSL mode if the server is SSL enabled
     * and it is not forced to operate in SSL mode
     */
    private function getToggleSSL() {
        $_useHttps = false;
        if (isset($GLOBALS['sys_https_host']) && $GLOBALS['sys_https_host']) {
            $_useHttps = true;
        }
        $toggle_ssl = false;
        if ($_useHttps && $GLOBALS['sys_force_ssl'] == 0 ) {
            $toggle_ssl = true;
        }

        return $toggle_ssl;
    }
}