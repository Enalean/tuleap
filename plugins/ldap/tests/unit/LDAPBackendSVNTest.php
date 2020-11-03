<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use Event;
use EventManager;
use LDAP_SVN_Apache_ModPerl;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SVN_Apache_Auth_Factory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalSVNPollution;

class LDAPBackendSVNTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;
    use GlobalSVNPollution;

    protected function setUp(): void
    {
        \ForgeConfig::set('svn_prefix', '/svnroot');
        \ForgeConfig::set('sys_name', 'Platform');
    }

    private function givenAFullApacheConf(): string
    {
        $backend  = \Mockery::mock(\LDAP_BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $project_array_01 = [
            'unix_group_name' => 'gpig',
            'group_name'      => 'Guinea Pig',
            'group_id'        => '101',
        ];

        $project_array_02 = [
            'unix_group_name' => 'garden',
            'group_name'      => 'The Garden Project',
            'group_id'        => '102',
        ];

        $svn_dao = \Mockery::spy(\SVN_DAO::class);
        $svn_dao->shouldReceive('searchSvnRepositories')->andReturns(\TestHelper::arrayToDar($project_array_01, $project_array_02));
        $backend->shouldReceive('getSvnDao')->andReturns($svn_dao);

        $plugin = new class extends \Plugin {
            public function svn_apache_auth(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                $ldap = \Mockery::spy(\LDAP::class);
                $ldap->shouldReceive('getLDAPParam')->with('server')->andReturns('ldap://ldap.tuleap.com');
                $ldap->shouldReceive('getLDAPParam')->with('dn')->andReturns('dc=tuleap,dc=com');

                $params['svn_apache_auth'] = new LDAP_SVN_Apache_ModPerl($ldap, $params['cache_parameters']);
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener(Event::SVN_APACHE_AUTH, $plugin, Event::SVN_APACHE_AUTH, false);

        $cache_parameters = new \Tuleap\SvnCore\Cache\Parameters(15, 20);

        $factory = new SVN_Apache_Auth_Factory($event_manager, $cache_parameters);

        $backend->shouldReceive('getSVNApacheAuthFactory')->andReturns($factory);

        return $backend->getApacheConf();
    }

    public function testFullConfShouldWrapEveryThing(): void
    {
        $conf = $this->givenAFullApacheConf();

        $this->assertMatchesRegularExpression('%TuleapLdapServers "ldap://ldap.tuleap.com"%', $conf);
        $this->thenThereAreTwoLocationDefinedGpigAndGarden($conf);
    }

    private function thenThereAreTwoLocationDefinedGpigAndGarden($conf): void
    {
        $matches = [];
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEquals('gpig', $matches[1][0]);
        $this->assertEquals('garden', $matches[1][1]);
    }
}
