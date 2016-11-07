<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

class SVN_Apache_Auth_FactoryTestEventManager extends EventManager {
    public function processEvent($event_name, $params) {
        $project_row = array();

        $params['svn_apache_auth'] = new SVN_Apache_ModFromPlugin($project_row);
    }
}

class SVN_Apache_Auth_FactoryTest extends TuleapTestCase {

    private $event_manager;
    private $project_manager;
    private $token_manager;
    private $cache_parameters;
    private $factory;
    private $project_info;
    private $project;
    private $mod_from_plugin;

    public function setUp() {
        ForgeConfig::store();

        $this->event_manager                    = mock('EventManager');
        $this->event_manager_with_plugin_answer = new SVN_Apache_Auth_FactoryTestEventManager();
        $this->project_manager                  = mock('ProjectManager');
        $this->token_manager                    = mock('SVN_TokenUsageManager');
        $this->cache_parameters                 = mock('Tuleap\SvnCore\Cache\Parameters');

        $this->factory = new SVN_Apache_Auth_Factory(
            $this->project_manager,
            $this->event_manager,
            $this->token_manager,
            $this->cache_parameters
        );

        $this->factory_with_plugin_answer = new SVN_Apache_Auth_Factory(
            $this->project_manager,
            $this->event_manager_with_plugin_answer,
            $this->token_manager,
            $this->cache_parameters
        );

        $this->project         = mock('Project');
        $this->project_info    = array();
        $this->mod_from_plugin = new SVN_Apache_ModFromPlugin($this->project_info, 'modmysql');
    }

    public function tearDown() {
        ForgeConfig::restore();
    }

    public function itReturnsModMysqlByDefault() {
        $this->assertIsA($this->factory->get($this->project_info, 'modmysql'), 'SVN_Apache_ModMysql');
    }

    public function itReturnsModPerlIfPlatformConfiguredWithModPerl() {
        ForgeConfig::set(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY, SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);
        $this->assertIsA($this->factory->get($this->project_info, 'modperl'), 'SVN_Apache_ModPerl');
    }

    public function itReturnModPluginIfPluginAuthIsConfiguredForThisProject() {
        $this->assertIsA($this->factory_with_plugin_answer->get($this->project_info, 'modmysql'), 'SVN_Apache_ModFromPlugin');
    }

    public function itReturnModPluginIfPlugiAuthIsConfiguredForThisProjectAndDefaultConfigIsModPerl() {
        ForgeConfig::set(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY, SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL);

        $this->assertIsA($this->factory_with_plugin_answer->get($this->project_info, 'modmysql'), 'SVN_Apache_ModFromPlugin');
    }

    public function itReturnsModPerlIfProjectIsAuthorizedToUseTokens() {
        stub($this->project_manager)->getProjectFromDbRow($this->project_info)->returns($this->project);
        stub($this->token_manager)->isProjectAuthorizingTokens($this->project)->returns(true);

        $this->assertIsA($this->factory->get($this->project_info, 'modmysql'), 'SVN_Apache_ModPerl');
    }
}