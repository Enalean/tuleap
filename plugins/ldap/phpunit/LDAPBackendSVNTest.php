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

use EventManager;
use LDAP_SVN_Apache_ModPerl;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SVN_Apache_Auth_Factory;

class LDAPBackendSVNTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->globals = $GLOBALS;
        $GLOBALS = [];
        $GLOBALS['svn_prefix'] = '/svnroot';
        $GLOBALS['sys_name']   = 'Platform';
    }

    protected function tearDown(): void
    {
        $GLOBALS = $this->globals;
        parent::tearDown();
    }

    private function givenAFullApacheConf(): string
    {
        $backend  = \Mockery::mock(\LDAP_BackendSVN::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $project_array_01 = array(
            'repository_name' => 'gpig',
            'group_name'      => 'Guinea Pig',
            'public_path'     => '/svnroot/gpig',
            'system_path'     => '/svnroot/gpig',
            'group_id'        => 101
        );

        $project_array_02 = array(
            'repository_name' => 'garden',
            'public_path'     => '/svnroot/garden',
            'system_path'     => '/svnroot/garden',
            'group_name'      => 'The Garden Project',
            'group_id'        => 102
        );

        $svn_dao = \Mockery::spy(\SVN_DAO::class);
        $svn_dao->shouldReceive('searchSvnRepositories')->andReturns(\TestHelper::arrayToDar($project_array_01, $project_array_02));
        $backend->shouldReceive('getsvnDao')->andReturns($svn_dao);

        $ldap = \Mockery::spy(\LDAP::class);
        $ldap->shouldReceive('getLDAPParam')->with('server')->andReturns('ldap://ldap.tuleap.com');
        $ldap->shouldReceive('getLDAPParam')->with('dn')->andReturns('dc=tuleap,dc=com');

        $event_manager = Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')
            ->with(Mockery::any(), Mockery::on(function (array $params) {
                $ldap             = \Mockery::spy(\LDAP::class);
                $cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);
                $params['svn_apache_auth'] = new LDAP_SVN_Apache_ModPerl($ldap, $cache_parameters, $params['project_info']);
                return true;
            }))
            ->twice();

        $cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);

        $factory = new SVN_Apache_Auth_Factory($event_manager, $cache_parameters);

        $backend->shouldReceive('getSVNApacheAuthFactory')->andReturns($factory);

        return $backend->getApacheConf();
    }

    public function testFullConfShouldWrapEveryThing(): void
    {
        $conf = $this->givenAFullApacheConf();

        $this->assertMatchesRegularExpression('/TuleapLdapServers/', $conf);
        $this->thenThereAreTwoLocationDefinedGpigAndGarden($conf);
    }

    private function thenThereAreTwoLocationDefinedGpigAndGarden($conf): void
    {
        $matches = array();
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEquals('gpig', $matches[1][0]);
        $this->assertEquals('garden', $matches[1][1]);
    }
}
