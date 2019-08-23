<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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


class SVN_Apache_ModPerlTest extends TuleapTestCase
{
    private function setConfForGuineaPigProject()
    {
        return array('unix_group_name' => 'gpig',
                     'public_path'     => '/svnroot/gpig',
                     'system_path'     => '/svnroot/gpig',
                     'group_name'      => 'Guinea Pig',
                     'group_id'        => 101);
    }

    /**
     * @return SVN_Apache_ModPerl
     */
    private function GivenAnApacheAuthenticationConfForGuineaPigProject()
    {
        return new SVN_Apache_ModPerl(mock('Tuleap\SvnCore\Cache\Parameters'), $this->setConfForGuineaPigProject());
    }

    public function testGetSVNApacheConfHeadersShouldInsertModPerl()
    {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertPattern('/PerlLoadModule Apache::Tuleap/', $conf->getHeaders());
    }

    public function testGetApacheAuthShouldContainsDefaultValues()
    {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }

    public function testGetApacheAuthShouldSetupPerlAccess()
    {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertPattern('/PerlAccessHandler/', $conf);
        $this->assertPattern('/TuleapDSN/', $conf);
    }

    public function testGetApacheAuthShouldNotReferenceAuthMysql()
    {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
    }

    public function itShouldUseCacheParameters()
    {
        $cache_parameters = mock('Tuleap\SvnCore\Cache\Parameters');
        stub($cache_parameters)->getMaximumCredentials()->returns(877);
        stub($cache_parameters)->getLifetime()->returns(947);

        $apache_modperl          = new SVN_Apache_ModPerl($cache_parameters, $this->setConfForGuineaPigProject());
        $project_db_row          = $this->setConfForGuineaPigProject();
        $generated_configuration = $apache_modperl->getConf(
            $project_db_row['public_path'],
            $project_db_row['system_path']
        );

        $this->assertStringContains($generated_configuration, 'TuleapCacheCredsMax 877');
        $this->assertStringContains($generated_configuration, 'TuleapCacheLifetime 947');
    }
}
