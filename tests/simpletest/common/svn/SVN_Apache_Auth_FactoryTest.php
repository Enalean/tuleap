<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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


class SVN_Apache_ModFromPlugin extends SVN_Apache
{
    protected function getProjectAuthentication($row)
    {
    }
}

class SVN_Apache_Auth_FactoryTestEventManager extends EventManager
{
    public function processEvent($event_name, $params = [])
    {
        $project_row = array();

        $params['svn_apache_auth'] = new SVN_Apache_ModFromPlugin($project_row);
    }
}

class SVN_Apache_Auth_FactoryTest extends TuleapTestCase
{
    private $event_manager;
    private $cache_parameters;
    private $factory;
    private $project_info;
    private $mod_from_plugin;

    public function setUp()
    {
        $this->event_manager                    = \Mockery::mock(\EventManager::class);
        $this->event_manager_with_plugin_answer = new SVN_Apache_Auth_FactoryTestEventManager();
        $this->cache_parameters                 = mock('Tuleap\SvnCore\Cache\Parameters');

        $this->factory = new SVN_Apache_Auth_Factory(
            $this->event_manager,
            $this->cache_parameters
        );

        $this->factory_with_plugin_answer = new SVN_Apache_Auth_Factory(
            $this->event_manager_with_plugin_answer,
            $this->cache_parameters
        );

        $this->project_info    = array();
        $this->mod_from_plugin = new SVN_Apache_ModFromPlugin($this->project_info);
    }

    public function itReturnsModPerlByDefault()
    {
        $this->event_manager->shouldReceive('processEvent');
        $this->assertIsA($this->factory->get($this->project_info), 'SVN_Apache_ModPerl');
    }

    public function itReturnModPluginIfPluginAuthIsConfiguredForThisProject()
    {
        $this->assertIsA($this->factory_with_plugin_answer->get($this->project_info), 'SVN_Apache_ModFromPlugin');
    }
}
