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

require_once 'common/user/LoginPresenter.class.php';

class OpenId_LoginPresenter extends User_LoginPresenter {

    public function __construct(User_LoginPresenter $login_presenter) {
        parent::__construct(
            $login_presenter->getPurifier(),
            $login_presenter->getReturnTo(),
            $login_presenter->getPv(),
            $login_presenter->getFormLoginName(),
            $login_presenter->getToggleSsl()
        );
    }

    public function getTemplateDir() {
        return dirname(dirname(__FILE__)) . '/templates/';
    }

    private function returnLoginUrl($openid_url) {
        $query_parts = array(
            'func'       => OpenId_OpenIdRouter::LOGIN,
            'openid_url' => $openid_url,
        );

        if ($this->getReturnTo()) {
            $query_parts['return_to'] = $this->getReturnTo();
        }

        return OPENID_BASE_URL.'/index.php?'.http_build_query($query_parts);
    }

    public function openid_google() {
        return $this->returnLoginUrl('https://www.google.com/accounts/o8/id');
    }

    public function openid_yahoo() {
        return $this->returnLoginUrl('https://me.yahoo.com/');
    }

    public function openid_generic_action() {
        return OPENID_BASE_URL.'/index.php';
    }

    public function openid_generic_function() {
        return OpenId_OpenIdRouter::LOGIN;
    }

    public function openid_submit() {
        return $GLOBALS['Language']->getText('account_login', 'openid_submit');
    }

    public function account_login_login_with_openid() {
        return $GLOBALS['Language']->getText('account_login', 'login_with_openid');
    }
}

?>
