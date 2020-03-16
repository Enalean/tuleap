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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

require_once __DIR__ . '/bootstrap.php';

/**
 * Bug identified when some attributes are not returned by default by the LDAP server
 * It was the case for 'eduid' and this element must be present so Tuleap can
 * link a user account and the LDAP account.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=7151
 */
class LDAPRetrieveAllArgumentsTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $ldap_params = array(
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    );

    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->ldap = \Mockery::mock(
            \LDAP::class,
            [$this->ldap_params, $this->logger]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItSearchesLoginWithAllAttributesExplicitly(): void
    {
        $this->ldap->shouldReceive('search')->with('dc=tuleap,dc=local', 'uid=john doe', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchLogin('john doe');
    }

    public function testItSearchesEduidWithAllAttributesExplicitly(): void
    {
        $this->ldap->shouldReceive('search')->with('dc=tuleap,dc=local', 'uuid=edx887', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchEdUid('edx887');
    }

    public function testItSearchesDNWiWithAllAttributesExplicitlyByDefault(): void
    {
        $this->ldap->shouldReceive('search')->with('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local');
    }

    public function testItSearchesDNWiWithExpectedAttributes(): void
    {
        $this->ldap->shouldReceive('search')->with('dn=edx887,dc=tuleap,dc=local', 'objectClass=*', LDAP::SCOPE_BASE, array('mail', 'uuid'))->once();

        $this->ldap->searchDn('dn=edx887,dc=tuleap,dc=local', array('mail', 'uuid'));
    }

    public function testItSearchesCommonNameWithAllAttributesExplicitly(): void
    {
        $this->ldap->shouldReceive('search')->with('dc=tuleap,dc=local', 'cn=John Snow', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchCommonName('John Snow');
    }

    public function testItSearchesUsersWithAllAttributesExplicitly(): void
    {
        $this->ldap->shouldReceive('search')->with('dc=tuleap,dc=local', '(|(uid=John Snow)(cn=John Snow)(mail=John Snow))', LDAP::SCOPE_SUBTREE, array('mail', 'cn', 'uid', 'uuid', 'dn'))->once();

        $this->ldap->searchUser('John Snow');
    }
}
