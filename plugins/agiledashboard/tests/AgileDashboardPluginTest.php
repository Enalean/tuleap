<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../include/agiledashboardPlugin.class.php';
require_once 'common/include/Codendi_Request.class.php';
require_once 'common/project/ProjectManager.class.php';
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');

class AgileDashboardPluginTest extends TuleapTestCase {
    
    public function testProcessShouldDisplayTheService() {
        $service = new MockService();
        
        $project = new MockProject();
        $project->setReturnValue('getService', $service, array('plugin_agiledashboard'));
        
        $manager = new MockProjectManager();
        $manager->setReturnValue('getProject', $project, array('66'));
        
        $request = new Codendi_Request(array('group_id' => '66'));
        
        $plugin = TestHelper::getPartialMock('AgileDashboardPlugin', array('displayService'));
        $plugin->expectOnce('displayService');
        
        $plugin->process($request, $manager, $GLOBALS['Language'], $GLOBALS['HTML']);
    }
    
    public function testProcessShouldDisplayAnErrorMessageIfServiceIsNotUsed() {
        $project = new MockProject();
        $project->setReturnValue('getService', null, array('plugin_agiledashboard'));
        $project->setReturnValue('getUnixName', 'coin');
        
        $manager = new MockProjectManager();
        $manager->setReturnValue('getProject', $project, array('66'));
        
        $request = new Codendi_Request(array('group_id' => '66'));
        
        $plugin = TestHelper::getPartialMock('AgileDashboardPlugin', array('displayService'));
        $plugin->expectNever('displayService');
        
        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/projects/coin/'));
        
        $plugin->process($request, $manager, $GLOBALS['Language'], $GLOBALS['HTML']);
    }
}
?>
