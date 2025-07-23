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

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVNCore\CoreApacheConfRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SVN_Apache_SvnrootConfTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use ForgeConfigSandbox;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'Platform');
        ForgeConfig::set('svn_prefix', '/bla');

        ForgeConfig::set('sys_custom_dir', vfsStream::setup('root', null, ['conf' => []])->url());
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

        return new SVN_Apache_SvnrootConf(new SVN_Apache(), $repositories);
    }

    private function givenAFullApacheConf(): string
    {
        $svnroot = $this->givenSvnrootForTwoGroups();
        return $svnroot->getFullConf();
    }

    public function testConfGeneration(): void
    {
        $conf = $this->givenAFullApacheConf();

        $this->assertStringContainsString('AuthBasicProvider anon', $conf);
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

        $conf = $this->givenAFullApacheConf();
        $this->assertMatchesRegularExpression('%\${APACHE_LOG_DIR}/tuleap_svn\.log%', $conf);
    }
}
