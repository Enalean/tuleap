<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/gitPlugin.class.php';
require_once dirname(__FILE__).'/../include/Git_Ci.class.php';
Mock::generatePartial('Git_Ci', 'Git_CiTestVersion', array('getDao', 'getProjectManager'));
require_once dirname(__FILE__).'/../include/Git_CiDao.class.php';
Mock::generate('Git_CiDao');
require_once 'common/project/ProjectManager.class.php';
Mock::generate('ProjectManager');
require_once 'common/project/Project.class.php';
Mock::generate('Project');
require_once 'common/dao/include/DataAccessResult.class.php';
Mock::generate('DataAccessResult');
require_once 'common/language/BaseLanguage.class.php';
Mock::generate('BaseLanguage');

class Git_CiTest extends UnitTestCase {

    function setup() {
        $GLOBALS['Language'] = new MockBaseLanguage();
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testRetrieveTriggersInvalidParams() {
        $gitCi = new Git_Ci();
        $this->assertEqual(null, $gitCi->retrieveTriggers(array('blah' => 1)));
    }

    function testRetrieveTriggersPluginNotUsed() {
        $project = new MockProject();
        $project->setReturnValue('usesService', false);
        $pm = new MockProjectManager();
        $pm->setReturnValue('getProject', $project);
        $gitCi = new Git_CiTestVersion();
        $gitCi->setReturnValue('getProjectManager', $pm);
        $this->assertEqual(null, $gitCi->retrieveTriggers(array('group_id' => 1)));
    }

    function testRetrieveTriggersWithJobId() {
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('rowCount', 1);
        $dar->setReturnValue('getRow', array('repository_id' => 1));
        $dao = new MockGit_CiDao();
        $dao->setReturnValue('retrieveTrigger', $dar);
        $dao->setReturnValue('retrieveTriggers', null);
        $project = new MockProject();
        $project->setReturnValue('usesService', true);
        $pm = new MockProjectManager();
        $pm->setReturnValue('getProject', $project);
        $gitCi = new Git_CiTestVersion();
        $gitCi->setReturnValue('getProjectManager', $pm);
        $gitCi->setReturnValue('getDao', $dao);
        $addForm  = '<p>
                                 <div id="hudson_use_plugin_git_trigger_form">
                                     <label for="hudson_use_plugin_git_trigger">'.$GLOBALS['Language']->getText('plugin_git', 'ci_repo_id').': </label>
                                     <input id="hudson_use_plugin_git_trigger" name="hudson_use_plugin_git_trigger" value="1" />
                                 </div>
                                 <div id="hudson_use_plugin_git_trigger_checkbox">
                                     Git 
                                     <input onclick="toggle_checkbox()" type="checkbox" checked="checked" />
                                 </div>
                                 <script>
                                     function toggle_checkbox() {
                                         Effect.toggle(\'hudson_use_plugin_git_trigger_form\', \'slide\', { duration: 0.3 });
                                         Effect.toggle(\'hudson_use_plugin_git_trigger_checkbox\', \'slide\', { duration: 0.3 });
                                     }
                                     Element.toggle(\'hudson_use_plugin_git_trigger_form\', \'slide\', { duration: 0.3 })
                                 </script>
                             </p>';
        $editForm = '<label for="new_hudson_use_plugin_git_trigger">'.$GLOBALS['Language']->getText('plugin_git', 'ci_field_description').': </label><input id="new_hudson_use_plugin_git_trigger" name="new_hudson_use_plugin_git_trigger" value="1" />';
        $result = array('service'       => GitPlugin::SERVICE_SHORTNAME,
                        'title'         => $GLOBALS['Language']->getText('plugin_git', 'ci_trigger'),
                        'used'          => array(),
                        'add_form'      => $addForm,
                        'edit_form'     => $editForm);
        $this->assertEqual($result, $gitCi->retrieveTriggers(array('group_id' => 1, 'job_id' => 1)));
    }

    function testRetrieveTriggersNoJobId() {
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('rowCount', 1);
        $dao = new MockGit_CiDao();
        $dao->setReturnValue('retrieveTrigger', null);
        $dao->setReturnValue('retrieveTriggers', $dar);
        $project = new MockProject();
        $project->setReturnValue('usesService', true);
        $pm = new MockProjectManager();
        $pm->setReturnValue('getProject', $project);
        $gitCi = new Git_CiTestVersion();
        $gitCi->setReturnValue('getProjectManager', $pm);
        $gitCi->setReturnValue('getDao', $dao);
        $addForm  = '<p>
                                 <div id="hudson_use_plugin_git_trigger_form">
                                     <label for="hudson_use_plugin_git_trigger">'.$GLOBALS['Language']->getText('plugin_git', 'ci_repo_id').': </label>
                                     <input id="hudson_use_plugin_git_trigger" name="hudson_use_plugin_git_trigger" value="" />
                                 </div>
                                 <div id="hudson_use_plugin_git_trigger_checkbox">
                                     Git 
                                     <input onclick="toggle_checkbox()" type="checkbox"  />
                                 </div>
                                 <script>
                                     function toggle_checkbox() {
                                         Effect.toggle(\'hudson_use_plugin_git_trigger_form\', \'slide\', { duration: 0.3 });
                                         Effect.toggle(\'hudson_use_plugin_git_trigger_checkbox\', \'slide\', { duration: 0.3 });
                                     }
                                     Element.toggle(\'hudson_use_plugin_git_trigger_form\', \'slide\', { duration: 0.3 })
                                 </script>
                             </p>';
        $editForm = '<label for="new_hudson_use_plugin_git_trigger">'.$GLOBALS['Language']->getText('plugin_git', 'ci_field_description').': </label><input id="new_hudson_use_plugin_git_trigger" name="new_hudson_use_plugin_git_trigger" value="" />';
        $result = array('service'       => GitPlugin::SERVICE_SHORTNAME,
                        'title'         => $GLOBALS['Language']->getText('plugin_git', 'ci_trigger'),
                        'used'          => array(),
                        'add_form'      => $addForm,
                        'edit_form'     => $editForm);
        $this->assertEqual($result, $gitCi->retrieveTriggers(array('group_id' => 1)));
    }

}

?>