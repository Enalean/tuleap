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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class User_LoginPresenter
{
    private $return_to;
    private $pv;
    private $form_loginname;
    private $display_new_account_button;
    private $allow_password_recovery;
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
        CSRFSynchronizerToken $csrf_token,
        string $prompt_parameter,
        public readonly \Tuleap\User\AdditionalConnectorsCollector $additional_connectors,
        $display_new_account_button = true,
        $allow_password_recovery = true,
    ) {
        $this->return_to                  = $return_to;
        $this->pv                         = $pv;
        $this->form_loginname             = $form_loginname;
        $this->display_new_account_button = $display_new_account_button;
        $this->allow_password_recovery    = $allow_password_recovery;
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

    public function form_loginname()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->getFormLoginName();
    }

    public function pv()
    {
        return $this->pv;
    }

    public function return_to()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->getReturnTo();
    }

    public function display_new_account_button()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->display_new_account_button;
    }

    public function allow_password_recovery()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->allow_password_recovery;
    }

    public function help_email()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return ForgeConfig::get('sys_email_admin');
    }

    public function not_a_member()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Not a member yet?');
    }

    public function create_account_label()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Create an account');
    }

    public function need_help()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'need_help');
    }

    public function help_subject()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return sprintf(_('Unable to login under %1$s'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
    }

    public function account_login_page_title()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'page_title', [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)]);
    }

    public function account_login_name()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'name');
    }

    public function account_login_password()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'password');
    }

    public function account_login_login_btn()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Login');
    }

    public function account_login_lost_pw()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Oops, I forgot my password');
    }

    public function account_login_login_with_tuleap()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Login');
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

    public function login_intro()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return file_get_contents($GLOBALS['Language']->getContent('account/login_intro', null, null, '.html'));
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
    public function csrf_token_name()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->csrf_token->getTokenName();
    }

    /**
     * @return string
     */
    public function csrf_token_value()//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->csrf_token->getToken();
    }

    public function getDisplayNewAccountButton()
    {
        return $this->display_new_account_button;
    }
}
