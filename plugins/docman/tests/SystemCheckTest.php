<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

class Docman_SystemCheckTest extends TuleapTestCase {

    /** @var Docman_SystemCheck */
    private $system_check;

    /** @var Plugin */
    private $plugin;

    /** @var Docman_SystemCheckProjectRetriever */
    private $retriever;

    public function setUp() {
        parent::setUp();

        ForgeConfig::store();
        ForgeConfig::set('sys_http_user', 'codendiadm');

        $this->plugin    = stub('Plugin')->getServiceShortname()->returns('docman');
        $this->retriever = mock('Docman_SystemCheckProjectRetriever');
        $logger          = mock('BackendLogger');
        $config_checker  = new PluginConfigChecker($logger);
        $backend         = BackendSystem::instance();

        $this->root_dir_path = dirname(__FILE__).'/_fixtures/';

        $plugin_info = stub('DocmanPluginInfo')->getPropertyValueForName('docman_root')->returns($this->root_dir_path);
        stub($this->plugin)->getPluginInfo()->returns($plugin_info);
        stub($this->plugin)->getPluginEtcRoot()->returns(ForgeConfig::get('codendi_cache_dir'));

        $this->system_check = new Docman_SystemCheck(
            $this->plugin,
            $this->retriever,
            $backend,
            $config_checker,
            $logger
        );
    }

    public function tearDown() {
        ForgeConfig::restore();
        rmdir($this->root_dir_path . 'project_01');

        parent::tearDown();
    }

    public function itCreatesFolderForActiveProject() {
        stub($this->retriever)->getActiveProjectUnixNamesThatUseDocman()->returns(array(
            'project_01'
        ));

        $this->system_check->process();

        $this->assertTrue(is_dir($this->root_dir_path . 'project_01'));
    }
}