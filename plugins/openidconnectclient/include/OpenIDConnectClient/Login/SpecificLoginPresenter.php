<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

    public function help_email()
    {
        return ForgeConfig::get('sys_email_admin');
    }

    public function need_help()
    {
        return $GLOBALS['Language']->getText('account_login', 'need_help');
    }

    public function help_subject()
    {
        return $GLOBALS['Language']->getText('account_login', 'help_subject', [ForgeConfig::get('sys_name')]);
    }

    public function account_login_login_with_tuleap()
    {
        return $GLOBALS['Language']->getText('account_login', 'login_with_tuleap', [ForgeConfig::get('sys_name')]);
    }

    public function login_intro()
    {
        return file_get_contents($GLOBALS['Language']->getContent('account/login_intro', null, null, '.html'));
    }
}
