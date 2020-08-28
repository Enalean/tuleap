<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\LDAP;

use CSRFSynchronizerToken;
use Project;
use Tuleap\User\UserGroup\NameTranslator;

class LinkModalContentPresenter
{
    public $ldap_group_name;
    public $project_id;
    public $is_preserved_members_checked;
    public $is_synchro_daily_checked;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $is_linked;
    public $display_name;
    public $action_label;
    public $locale;
    public $ldap_display_name;
    /**
     * @var string
     */
    public $ldap_server_common_name;

    public function __construct(
        $ldap_group_name,
        Project $project,
        $is_preserved_members_checked,
        $is_synchro_daily_checked,
        $is_linked,
        $action_label,
        $locale,
        CSRFSynchronizerToken $csrf_token,
        $ldap_display_name,
        string $ldap_server_common_name
    ) {
        $this->ldap_group_name              = $ldap_group_name;
        $this->project_id                   = $project->getID();
        $this->is_preserved_members_checked = $is_preserved_members_checked;
        $this->is_synchro_daily_checked     = $is_synchro_daily_checked;
        $this->is_linked                    = $is_linked;
        $this->csrf_token                   = $csrf_token;
        $this->display_name                 = NameTranslator::getUserGroupDisplayName(NameTranslator::PROJECT_MEMBERS);
        $this->action_label                 = $action_label;
        $this->locale                       = $locale;
        $this->ldap_display_name            = $ldap_display_name;
        $this->ldap_server_common_name      = $ldap_server_common_name;
    }
}
