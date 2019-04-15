<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'bootstrap.php';

/**
 * Bug identified when some attributes are not returned by default by the LDAP server
 * It was the case for 'eduid' and this element must be present so Tuleap can
 * link a user account and the LDAP account.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=7151
 */
class LDAP_RetrieveAllArguementsTest extends TuleapTestCase {

    private $ldap_params = array(
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    );

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->ldap = partial_mock(
            'LDAP',
            array('search'),
            array($this->ldap_params, mock('TruncateLevelLogger')));
    }

    function tearDown() {
        ForgeConfig::restore();
    }

    public function itSearchesLoginWithAllAttributesExplicitly() {
        expect($this->ldap)->search('dc=tuleap,dc=local', 'uid=john doe', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchLogin('john doe');
    }

    public function itSearchesEduidWithAllAttributesExplicitly() {
        expect($this->ldap)->search('dc=tuleap,dc=local', 'uuid=edx887', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchEdUid('edx887');
    }

    public function itSearchesDNWiWithAllAttributesExplicitlyByDefault() {
        expect($this->ldap)->search('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local');
    }

    public function itSearchesDNWiWithExpectedAttributes() {
        expect($this->ldap)->search('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, array('mail', 'uuid'))->once();

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local', array('mail', 'uuid'));
    }

    public function itSearchesCommonNameWithAllAttributesExplicitly() {
        expect($this->ldap)->search('dc=tuleap,dc=local', 'cn=John Snow', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchCommonName('John Snow');
    }

    public function itSearchesUsersWithAllAttributesExplicitly() {
        expect($this->ldap)->search('dc=tuleap,dc=local', '(|(uid=John Snow)(cn=John Snow)(mail=John Snow))', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchUser('John Snow');
    }
}
