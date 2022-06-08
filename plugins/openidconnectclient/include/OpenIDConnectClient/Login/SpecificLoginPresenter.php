<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login;

use ForgeConfig;

class SpecificLoginPresenter
{
    /**
     * @var array
     */
    public $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function help_email() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return ForgeConfig::get('sys_email_admin');
    }

    public function need_help() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'need_help');
    }

    public function help_subject() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return sprintf(_('Unable to login under %1$s'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
    }

    public function account_login_login_with_tuleap() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return _('Login');
    }

    public function login_intro() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return file_get_contents($GLOBALS['Language']->getContent('account/login_intro', null, null, '.html'));
    }
}
