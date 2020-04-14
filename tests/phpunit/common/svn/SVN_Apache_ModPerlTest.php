<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_Apache_ModPerlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function setConfForGuineaPigProject(): array
    {
        return array(
            'unix_group_name' => 'gpig',
            'public_path'     => '/svnroot/gpig',
            'system_path'     => '/svnroot/gpig',
            'group_name'      => 'Guinea Pig',
            'group_id'        => 101
        );
    }

    private function givenAnApacheAuthenticationConfForGuineaPigProject(): SVN_Apache_ModPerl
    {
        return new SVN_Apache_ModPerl(\Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class), $this->setConfForGuineaPigProject());
    }

    public function testGetSVNApacheConfHeadersShouldInsertModPerl(): void
    {
        $conf = $this->givenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertStringContainsString('PerlLoadModule Apache::Tuleap', $conf->getHeaders());
    }

    public function testGetApacheAuthShouldContainsDefaultValues(): void
    {
        $mod  = $this->givenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertMatchesRegularExpression('/Require valid-user/', $conf);
        $this->assertMatchesRegularExpression('/AuthType Basic/', $conf);
        $this->assertMatchesRegularExpression('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }

    public function testGetApacheAuthShouldSetupPerlAccess(): void
    {
        $mod  = $this->givenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertMatchesRegularExpression('/PerlAccessHandler/', $conf);
        $this->assertMatchesRegularExpression('/TuleapDSN/', $conf);
    }

    public function testGetApacheAuthShouldNotReferenceAuthMysql(): void
    {
        $mod  = $this->givenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertDoesNotMatchRegularExpression('/AuthMYSQLEnable/', $conf);
    }

    public function testItShouldUseCacheParameters(): void
    {
        $cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);
        $cache_parameters->shouldReceive('getMaximumCredentials')->andReturns(877);
        $cache_parameters->shouldReceive('getLifetime')->andReturns(947);

        $apache_modperl          = new SVN_Apache_ModPerl($cache_parameters, $this->setConfForGuineaPigProject());
        $project_db_row          = $this->setConfForGuineaPigProject();
        $generated_configuration = $apache_modperl->getConf(
            $project_db_row['public_path'],
            $project_db_row['system_path']
        );

        $this->assertStringContainsString('TuleapCacheCredsMax 877', $generated_configuration);
        $this->assertStringContainsString('TuleapCacheLifetime 947', $generated_configuration);
    }
}
