<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
 *
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\CoreApacheConfRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_Apache_SvnrootConfTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_name', 'Platform');
        ForgeConfig::set('sys_dbhost', 'db_server');
        ForgeConfig::set('sys_dbname', 'db');
        ForgeConfig::set('svn_prefix', '/bla');
        ForgeConfig::set('sys_dbauth_user', 'dbauth_user');
        ForgeConfig::set('sys_dbauth_passwd', 'dbauth_passwd');
    }

    private function givenSvnrootForTwoGroups(): SVN_Apache_SvnrootConf
    {
        $repositories = [
            new CoreApacheConfRepository(
                ProjectTestBuilder::aProject()->withId(101)->withUnixName('gpig')->withPublicName('Guinea Pig')->build(),
            ),
            new CoreApacheConfRepository(
                ProjectTestBuilder::aProject()->withId(102)->withUnixName('garden')->withPublicName('The Garden Project')->build(),
            ),
        ];

        $event_manager = new class extends EventManager
        {
            public function processEvent($event_name, $params = [])
            {
                $params['svn_apache_auth'] = null;
            }
        };

        $cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);

        $factory = new SVN_Apache_Auth_Factory($event_manager, $cache_parameters);

        return new SVN_Apache_SvnrootConf($factory, $repositories);
    }

    private function givenAFullApacheConfWithModPerl()
    {
        $svnroot = $this->givenSvnrootForTwoGroups();
        return $svnroot->getFullConf();
    }

    public function testFullConfShouldWrapEveryThing()
    {
        $conf = $this->givenAFullApacheConfWithModPerl();

        $this->assertMatchesRegularExpression('/PerlLoadModule Apache::Tuleap/', $conf);
        $this->thenThereAreTwoLocationDefinedGpigAndGarden($conf);
        $this->thenThereAreOnlyOneCustomLogStatement($conf);
    }

    private function thenThereAreTwoLocationDefinedGpigAndGarden($conf)
    {
        $matches = [];
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEquals('gpig', $matches[1][0]);
        $this->assertEquals('garden', $matches[1][1]);
    }

    private function thenThereAreOnlyOneCustomLogStatement($conf)
    {
        preg_match_all('/CustomLog/', $conf, $matches);
        $this->assertEquals(1, count($matches[0]));
    }

    public function testItHasALogFileFromConfiguration(): void
    {
        ForgeConfig::set(SVN_Apache_SvnrootConf::CONFIG_SVN_LOG_PATH, '${APACHE_LOG_DIR}/tuleap_svn.log');

        $conf = $this->givenAFullApacheConfWithModPerl();
        $this->assertMatchesRegularExpression('%\${APACHE_LOG_DIR}/tuleap_svn\.log%', $conf);
    }
}
