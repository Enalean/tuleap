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

class OpenId_PairAccountsPresenter {

    private function getPairingBaseUrl() {
        return OPENID_BASE_URL.'/index.php?func='.OpenId_OpenIdRouter::PAIR_ACCOUNTS.'&openid_url=';
    }

    public function openid_google() {
        return $this->getPairingBaseUrl().urlencode('https://www.google.com/accounts/o8/id');
    }

    public function openid_yahoo() {
        return $this->getPairingBaseUrl().urlencode('https://me.yahoo.com/');
    }

    public function openid_submit() {
        return $GLOBALS['Language']->getText('account_login', 'openid_submit');
    }

    public function account_login_pair_openid_title() {
        return $GLOBALS['Language']->getText('account_login', 'pair_openid_title');
    }

    public function account_login_login_with_openid() {
        return $GLOBALS['Language']->getText('account_login', 'pair_openid');
    }
}

?>
