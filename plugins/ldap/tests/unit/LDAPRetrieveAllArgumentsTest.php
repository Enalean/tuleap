<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\LDAP;

use ForgeConfig;
use LDAP;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;

/**
 * Bug identified when some attributes are not returned by default by the LDAP server
 * It was the case for 'eduid' and this element must be present so Tuleap can
 * link a user account and the LDAP account.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=7151
 */
final class LDAPRetrieveAllArgumentsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private array $ldap_params = [
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    ];

    private MockObject&LDAP $ldap;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->ldap = $this->getMockBuilder(\LDAP::class)
            ->setConstructorArgs([$this->ldap_params, new NullLogger()])
            ->onlyMethods(['search'])
            ->getMock();
    }

    public function testItSearchesLoginWithAllAttributesExplicitly(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dc=tuleap,dc=local', 'uid=john doe', LDAP::SCOPE_SUBTREE, ['mail', 'cn', 'uid', 'uuid', 'dn']);

        $this->ldap->searchLogin('john doe');
    }

    public function testItSearchesEduidWithAllAttributesExplicitly(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dc=tuleap,dc=local', 'uuid=edx887', LDAP::SCOPE_SUBTREE, ['mail', 'cn', 'uid', 'uuid', 'dn']);

        $this->ldap->searchEdUid('edx887');
    }

    public function testItSearchesDNWiWithAllAttributesExplicitlyByDefault(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, ['mail', 'cn', 'uid', 'uuid', 'dn']);

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local');
    }

    public function testItSearchesDNWiWithExpectedAttributes(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, ['mail', 'uuid']);

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local', ['mail', 'uuid']);
    }

    public function testItSearchesCommonNameWithAllAttributesExplicitly(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dc=tuleap,dc=local', 'cn=John Snow', LDAP::SCOPE_SUBTREE, ['mail', 'cn', 'uid', 'uuid', 'dn']);

        $this->ldap->searchCommonName('John Snow');
    }

    public function testItSearchesUsersWithAllAttributesExplicitly(): void
    {
        $this->ldap->expects(self::once())->method('search')->with('dc=tuleap,dc=local', '(|(uid=John Snow)(cn=John Snow)(mail=John Snow))', LDAP::SCOPE_SUBTREE, ['mail', 'cn', 'uid', 'uuid', 'dn']);

        $this->ldap->searchUser('John Snow');
    }
}
