<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;

class Account_RegisterPresenter
{

    public $prefill_values;
    public $login;
    public $email;
    public $email_tooltip;
    public $realname;
    public $siteupdate;
    public $purpose;
    public $purpose_directions;
    public $new_password;
    public $timezone_selector;
    public $should_display_purpose;
    public $json_password_strategy_keys;
    public $password_strategy_validators;
    public $legal = '';
    private $extra_plugin_field;

    public function __construct(Account_RegisterPrefillValuesPresenter $prefill_values, $extra_plugin_field)
    {
        $this->prefill_values         = $prefill_values;
        $this->login                  = _('Login Name');
        $this->email                  = _('Email');
        $this->email_tooltip          = _('We will send an email at this address to confirm your account.');
        $this->realname               = _('Full name');
        $this->siteupdate             = _('Receive Email about Site Updates <span class="label-help">Very low traffic, includes security notices. Highly Recommended.</span>');
        $this->purpose                = _('Purpose');
        $this->purpose_directions     = _('Please explain why you are applying for registration on this site<br>');
        $this->new_password           = _('Password');
        $this->timezone_selector      = new Account_TimezoneSelectorPresenter(
            $this->prefill_values->form_timezone->value
        );
        $this->should_display_purpose = ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1;
        $this->extra_plugin_field     = $extra_plugin_field;

        $password_configuration_retriever = new PasswordConfigurationRetriever(new PasswordConfigurationDAO());
        $password_configuration           = $password_configuration_retriever->getPasswordConfiguration();
        $password_strategy                = new PasswordStrategy($password_configuration);
        include($GLOBALS['Language']->getContent('account/password_strategy'));
        $this->json_password_strategy_keys  = json_encode(array_keys($password_strategy->validators));
        $this->password_strategy_validators = [];
        foreach ($password_strategy->validators as $key => $v) {
            $this->password_strategy_validators[] = [
                'key'         => $key,
                'description' => $v->description(),
            ];
        }
    }

    public function extra_plugin_field()
    {
        return $this->extra_plugin_field;
    }
}
