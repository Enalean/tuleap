<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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

require_once 'common/svn/SVN_Apache_Auth_Factory.class.php';

class SVN_Apache_ModFromPlugin extends SVN_Apache {
    protected function getProjectAuthentication($row) {}
}

class SVN_Apache_Auth_FactoryTest extends TuleapTestCase {

    private $event_manager;
    private $project_manager;
    private $token_manager;
    private $factory;
    private $project;
    private $mod_from_plugin;

    public function setUp() {
        ForgeConfig::store();

        $this->event_manager   = mock('EventManager');
        $this->project_manager = mock('ProjectManager');
        $this->token_manager   = mock('SVN_TokenUsageManager');

        $this->factory = partial_mock(
            'SVN_Apache_Auth_Factory',
            array('getModFromPlugins'),
            array($this->project_manager, $this->event_manager, $this->token_manager)
        );

        $this->project         = mock('Project');
        $this->mod_from_plugin = new SVN_Apache_ModFromPlugin($this->project);
    }

    public function tearDown() {
        ForgeConfig::restore();
    }

    public function itReturnsModMysqlByDefault() {
        $projectInfo = array();

        $this->assertIsA($this->factory->get($projectInfo), 'SVN_Apache_ModMysql');
    }

    public function itReturnsModPerlIfPlatformConfiguredWithModPerl() {
        $projectInfo = array();

        ForgeConfig::set(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY, SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);

        $this->assertIsA($this->factory->get($projectInfo), 'SVN_Apache_ModPerl');
    }

    public function itReturnModPluginIfPluginAuthIsConfiguredForThisProject() {
        $projectInfo = array();

        stub($this->factory)->getModFromPlugins()->returns($this->mod_from_plugin);

        $this->assertIsA($this->factory->get($projectInfo), 'SVN_Apache_ModFromPlugin');
    }

    public function itReturnModPluginIfPlugiAuthIsConfiguredForThisProjectAndDefaultConfigIsModPerl() {
        $projectInfo = array();

        ForgeConfig::set(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY, SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);
        stub($this->factory)->getModFromPlugins()->returns($this->mod_from_plugin);

        $this->assertIsA($this->factory->get($projectInfo), 'SVN_Apache_ModFromPlugin');
    }

    public function itReturnsModPerlIfProjectIsAuthorizedToUseTokens() {
        $projectInfo = array();

        stub($this->project_manager)->getProjectFromDbRow($projectInfo)->returns($this->project);
        stub($this->token_manager)->isProjectAuthorizingTokens($this->project)->returns(true);

        $this->assertIsA($this->factory->get($projectInfo), 'SVN_Apache_ModPerl');
    }
}