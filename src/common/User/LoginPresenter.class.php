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

class User_LoginPresenter
{
    private $return_to;
    private $pv;
    private $form_loginname;
    private $display_new_account_button;
    private $allow_password_recovery;
    private $additional_connectors;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var string
     * @psalm-readonly
     */
    public $prompt_parameter;

    public function __construct(
        $return_to,
        $pv,
        $form_loginname,
        $additional_connectors,
        CSRFSynchronizerToken $csrf_token,
        string $prompt_parameter,
        $display_new_account_button = true,
        $allow_password_recovery = true
    ) {
        $this->return_to                  = $return_to;
        $this->pv                         = $pv;
        $this->form_loginname             = $form_loginname;
        $this->display_new_account_button = $display_new_account_button;
        $this->allow_password_recovery    = $allow_password_recovery;
        $this->additional_connectors      = $additional_connectors;
        $this->csrf_token                 = $csrf_token;
        $this->prompt_parameter           = $prompt_parameter;
    }

    public function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') . '/src/templates/user';
    }

    public function getTemplate()
    {
        return 'login';
    }

    public function form_loginname()
    {
        return $this->getFormLoginName();
    }

    public function pv()
    {
        return $this->pv;
    }

    public function return_to()
    {
        return $this->getReturnTo();
    }

    public function display_new_account_button()
    {
        return $this->display_new_account_button;
    }

    public function allow_password_recovery()
    {
        return $this->allow_password_recovery;
    }

    public function help_email()
    {
        return ForgeConfig::get('sys_email_admin');
    }

    public function not_a_member()
    {
        return $GLOBALS['Language']->getText('account_login', 'not_a_member');
    }

    public function create_account_label()
    {
        return $GLOBALS['Language']->getText('account_login', 'create_account_label');
    }

    public function need_help()
    {
        return $GLOBALS['Language']->getText('account_login', 'need_help');
    }

    public function help_subject()
    {
        return $GLOBALS['Language']->getText('account_login', 'help_subject', array(ForgeConfig::get('sys_name')));
    }

    public function account_login_page_title()
    {
        return $GLOBALS['Language']->getText('account_login', 'page_title', array(ForgeConfig::get('sys_name')));
    }

    public function account_login_name()
    {
        return $GLOBALS['Language']->getText('account_login', 'name');
    }

    public function account_login_password()
    {
        return $GLOBALS['Language']->getText('account_login', 'password');
    }

    public function account_login_login_btn()
    {
        return $GLOBALS['Language']->getText('account_login', 'login_btn');
    }

    public function account_login_lost_pw()
    {
        return $GLOBALS['Language']->getText('account_login', 'lost_pw');
    }

    public function account_login_login_with_tuleap()
    {
        return $GLOBALS['Language']->getText('account_login', 'login_with_tuleap', array(ForgeConfig::get('sys_name')));
    }

    public function getReturnTo()
    {
        return $this->return_to;
    }

    public function getPv()
    {
        return $this->pv;
    }

    public function getFormLoginName()
    {
        return $this->form_loginname;
    }

    public function login_intro()
    {
        return file_get_contents($GLOBALS['Language']->getContent('account/login_intro', null, null, '.html'));
    }

    public function additional_connectors()
    {
        return $this->additional_connectors;
    }

    /**
     * @return CSRFSynchronizerToken
     */
    public function getCSRFToken()
    {
        return $this->csrf_token;
    }

    /**
     * @return string
     */
    public function csrf_token_name()
    {
        return $this->csrf_token->getTokenName();
    }

    /**
     * @return string
     */
    public function csrf_token_value()
    {
        return $this->csrf_token->getToken();
    }

    public function getDisplayNewAccountButton()
    {
        return $this->display_new_account_button;
    }
}
