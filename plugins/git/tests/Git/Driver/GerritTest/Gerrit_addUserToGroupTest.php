<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/GerritTestBase.php';

interface Git_Driver_Gerrit_addUserToGroupTest {
    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI();
    public function itExecutesTheInsertCommand();
}

class Git_Driver_GerritLegacy_addUserToGroupTest extends Git_Driver_GerritLegacy_baseTest implements Git_Driver_Gerrit_addUserToGroupTest {

    private $groupname;
    private $ldap_uid;
    private $user;
    private $account_id;
    private $group_id;

    public function setUp() {
        parent::setUp();
        $this->group                       = 'contributors';
        $this->groupname                   = $this->project_name.'/'.$this->namespace.'/'.$this->repository_name.'-'.$this->group;
        $this->ldap_uid                    = 'someuser';
        $this->user                        = new Git_Driver_Gerrit_User(stub('LDAP_User')->getUid()->returns($this->ldap_uid));

        $this->insert_member_query = 'gerrit gsql --format json -c "INSERT\ INTO\ account_group_members\ (account_id,\ group_id)\ SELECT\ A.account_id,\ G.group_id\ FROM\ account_external_ids\ A,\ account_groups\ G\ WHERE\ A.external_id=\\\'username:'. $this->ldap_uid .'\\\'\ AND\ G.name=\\\''. $this->groupname .'\\\'"';

        $this->set_account_query   = 'gerrit set-account '.$this->ldap_uid;
    }

    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI() {
        expect($this->ssh)->execute($this->gerrit_server, $this->set_account_query)->at(0);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }

    public function itExecutesTheInsertCommand() {
        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $this->insert_member_query)->at(1);

        $this->driver->addUserToGroup($this->gerrit_server, $this->user, $this->groupname);
    }
}

class Git_Driver_GerritREST_addUserToGroupTest extends Git_Driver_GerritREST_baseTest implements Git_Driver_Gerrit_addUserToGroupTest {
    public function itExecutesTheInsertCommand() {}
    public function itInitializeUserAccountInGerritWhenUserNeverLoggedToGerritUI() {}
}