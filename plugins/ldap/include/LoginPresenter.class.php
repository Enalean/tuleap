<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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


class LDAP_LoginPresenter extends User_LoginPresenter
{
    /**
     * @var string
     */
    private $ldap_server_common_name;

    public function __construct(User_LoginPresenter $login_presenter, string $ldap_server_common_name)
    {
        parent::__construct(
            $login_presenter->getReturnTo(),
            $login_presenter->getPv(),
            $login_presenter->getFormLoginName(),
            $login_presenter->getCSRFToken(),
            $login_presenter->prompt_parameter,
            $login_presenter->additional_connectors,
            $login_presenter->getDisplayNewAccountButton(),
            false
        );
        $this->ldap_server_common_name = $ldap_server_common_name;
    }

    public function account_login_login_with_tuleap()
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'page_title', [$this->ldap_server_common_name]);
    }
}
