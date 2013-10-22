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

class User_LoginPresenter {

    /** @var Codendi_HTMLPurifier */
    private $purifier;

    private $return_to;
    private $pv;
    private $form_loginname;
    private $toggle_ssl;
    private $allow_password_recovery;

    public function __construct(
        Codendi_HTMLPurifier $purifier,
            $return_to,
            $pv,
            $form_loginname,
            $toggle_ssl,
            $allow_password_recovery = true
        ) {
        $this->purifier = $purifier;
        $this->return_to = $return_to;
        $this->pv = $pv;
        $this->form_loginname = $form_loginname;
        $this->toggle_ssl = $toggle_ssl;
        $this->allow_password_recovery = $allow_password_recovery;
    }

    public function getTemplateDir() {
        return Config::get('codendi_dir') .'/src/templates/user';
    }

    public function getTemplate() {
        return 'login';
    }

    public function toggle_ssl() {
        return true;
        return $this->toggle_ssl;
    }

    public function form_loginname() {
        return $this->purifier->purify($this->form_loginname);
    }

    public function pv() {
        return $this->pv;
    }

    public function return_to() {
        return $this->purifier->purify($this->return_to);
    }

    public function allow_password_recovery() {
        return $this->allow_password_recovery;
    }

    public function help() {
        return $GLOBALS['Language']->getText('account_login', 'help', array(Config::get('sys_email_admin'), Config::get('sys_name')));
    }

    public function cookies() {
        return $GLOBALS['Language']->getText('account_login', 'cookies');
    }

    public function stay_in_ssl() {
        return $GLOBALS['Language']->getText('account_login', 'stay_ssl');
    }

    public function account_login_page_title() {
        return $GLOBALS['Language']->getText('account_login', 'page_title', array(Config::get('sys_name')));
    }

    public function account_login_name() {
        return $GLOBALS['Language']->getText('account_login', 'name');
    }

    public function account_login_password() {
        return $GLOBALS['Language']->getText('account_login', 'password');
    }

    public function account_login_login_btn() {
        return $GLOBALS['Language']->getText('account_login', 'login_btn');
    }

    public function account_login_lost_pw() {
        return $GLOBALS['Language']->getText('account_login', 'lost_pw');
    }

    public function account_login_login_with_tuleap() {
        return $GLOBALS['Language']->getText('account_login', 'login_with_tuleap', array(Config::get('sys_name')));
    }

    public function getPurifier() {
        return $this->purifier;
    }

    public function getReturnTo() {
        return $this->return_to;
    }

    public function getPv() {
        return $this->pv;
    }

    public function getFormLoginName() {
        return $this->form_loginname;
    }

    public function getToggleSsl() {
        return $this->toggle_ssl;
    }

    public function login_intro() {
        return file_get_contents($GLOBALS['Language']->getContent('account/login_intro', null, null, '.html'));
    }
}

?>
