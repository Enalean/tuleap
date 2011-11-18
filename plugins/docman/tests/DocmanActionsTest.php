<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/*require_once(dirname(__FILE__).'/../include/Docman_VersionFactory.class.php');
Mock::generatePartial('Docman_VersionFactory','Docman_VersionFactoryTest', array('_getVersionDao',));

require_once(dirname(__FILE__).'/../include/Docman_VersionDao.class.php');
Mock::generate('Docman_VersionDao');

require_once('common/project/Project.class.php');
Mock::generate('Project');*/

require_once(dirname(__FILE__).'/../include/Docman_Actions.class.php');
Mock::generatePartial('Docman_Actions','Docman_ActionsTest', array('_getItemFactory',
                                                                   '_getFileStorage',
                                                                   '_getActionsDeleteVisitor',
                                                                   '_getEventManager',
                                                                   '_getVersionFactory',
                                                                   '_getUserManagerInstance'));

require_once(dirname(__FILE__).'/../include/Docman_Controller.class.php');
Mock::generate('Docman_Controller');

require_once('common/valid/ValidFactory.class.php');

Mock::generate('HTTPRequest');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_Folder');
Mock::generate('Docman_File');
Mock::generate('Feedback');
Mock::generate('Docman_VersionFactory');
Mock::generate('Docman_Version');
Mock::generate('Docman_Item');
Mock::generate('Docman_NotificationsManager');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

Mock::generate('EventManager');
Mock::generate('UserManager');
Mock::generate('User');

class DocmanActionsTest extends UnitTestCase {

    function __construct($name = 'DocmanActions test') {
        parent::__construct($name);
    }

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    
    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testCannotDeleteVersionOnNonFile() {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('error', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'error_item_not_deleted_nonfile_version'));

        // Setup of the test
        $actions = new Docman_ActionsTest($this);

        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);
        $actions->_controler = $ctrl;

        $item = new MockDocman_Folder($this);
        $if   = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item);
        $if->expectOnce('getItemFromDb', array(344));
        $if->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));

        $actions->setReturnValue('_getEventManager', new MockEventManager($this));

        // Run test
        $actions->deleteVersion();
    }
    
    function testCanDeleteVersionOfFile() {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('info', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'info_item_version_deleted', array(1, 'label 5')));

        // Setup of the test
        $actions = new Docman_ActionsTest($this);

        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);
        $actions->_controler = $ctrl;

        $item = new MockDocman_File($this);
        $item->setReturnValue('accept', true);

        $if = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item, array(344));
        $if->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));

        $vf = new MockDocman_VersionFactory($this);
        $v1 = new MockDocman_Version($this);
        $v1->setReturnValue('getNumber', 0);
        $v1->setReturnValue('getLabel', 'label 4');
        $v2 = new MockDocman_Version($this);
        $v2->setReturnValue('getNumber', 1);
        $v2->setReturnValue('getLabel', 'label 5');
        $vf->setReturnValue('getAllVersionForItem', array($v1, $v2));
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', new MockEventManager($this));

        // Run test
        $actions->deleteVersion();
    }

    function testCannotDeleteLastVersion() {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('error', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'error_item_not_deleted_last_file_version'));

        // Setup of the test
        $actions = new Docman_ActionsTest($this);

        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);
        $actions->_controler = $ctrl;

        $item = new MockDocman_File($this);
        $item->setReturnValue('accept', true);

        $if = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item, array(344));
        $if->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));

        $vf = new MockDocman_VersionFactory($this);
        $vf->setReturnValue('getAllVersionForItem', array(new MockDocman_Version($this)));
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', new MockEventManager($this));

        // Run test
        $actions->deleteVersion();
    }
    
    function testCannotDeleteNonExistantVersion() {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = new MockDocman_Controller($this);
        $ctrl->feedback = new MockFeedback($this);
        // Test log message
        $ctrl->feedback->expectOnce('log', array('error', '*'));
        $GLOBALS['Language']->setReturnValue('getText', 'bla');
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'error_item_not_deleted_unknown_version'));

        // Setup of the test
        $actions = new Docman_ActionsTest($this);

        $ctrl->request = new MockHTTPRequest($this);
        $ctrl->request->setReturnValue('get', '102', array('group_id'));
        $ctrl->request->setReturnValue('get', '344', array('id'));
        $ctrl->request->setReturnValue('get', '1', array('version'));
        $ctrl->request->setReturnValue('valid', true);
        $actions->_controler = $ctrl;

        $item = new MockDocman_File($this);
        $item->expectNever('accept');

        $if = new MockDocman_ItemFactory($this);
        $if->setReturnValue('getItemFromDb', $item, array(344));
        $if->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->setReturnValue('_getItemFactory', $if);
        $actions->expectOnce('_getItemFactory', array(102));

        $vf = new MockDocman_VersionFactory($this);
        $v1 = new MockDocman_Version($this);
        $v1->setReturnValue('getNumber', 0);
        $v1->setReturnValue('getLabel', 'label 4');
        $v2 = new MockDocman_Version($this);
        $v2->setReturnValue('getNumber', 2);
        $v2->setReturnValue('getLabel', 'label 5');
        $vf->setReturnValue('getAllVersionForItem', array($v1, $v2));
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', new MockEventManager($this));

        // Run test
        $actions->deleteVersion();
    }

    function testRemove_monitoringNothingToDelete() {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->remove_monitoring(array('listeners_to_delete' => true));
        $controller->expectNever('userCanManage');
    }

    function testRemove_monitoringPermissionDenied() {
        $controller = new MockDocman_Controller();
        $controller->setReturnValue('userCanManage', false);
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->setReturnValue('_getUserManagerInstance', $userManager);
        $params['listeners_to_delete'] = array(1);
        $params['item'] = new MockDocman_Item();
        $actions->remove_monitoring($params);
        $controller->expectOnce('userCanManage');
        $userManager->expectNever('getUserById');
    }

    function testRemove_monitoringNotifDoesNotExist() {
        $controller = new MockDocman_Controller();
        $controller->setReturnValue('userCanManage', true);
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $user = new MockUser();
        $userManager->setReturnValue('getUserById', $user);
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('exist', false);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->setReturnValue('_getUserManagerInstance', $userManager);
        $params['listeners_to_delete'] = array(1, 2, 3);
        $params['item'] = new MockDocman_Item();
        $actions->remove_monitoring($params);
        $controller->expectOnce('userCanManage');
        $userManager->expectCallCount('getUserById', 3);
        $notificationsManager->expectCallCount('exist', 3);
        $notificationsManager->expectNever('remove');
    }

    function testRemove_monitoringError() {
        $controller = new MockDocman_Controller();
        $controller->setReturnValue('userCanManage', true);
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $user = new MockUser();
        $userManager->setReturnValue('getUserById', $user);
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('exist', true);
        $notificationsManager->setReturnValue('remove', false);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->setReturnValue('_getUserManagerInstance', $userManager);
        $params['listeners_to_delete'] = array(1, 2, 3);
        $params['item'] = new MockDocman_Item();
        $actions->remove_monitoring($params);
        $controller->expectOnce('userCanManage');
        $userManager->expectCallCount('getUserById', 3);
        $notificationsManager->expectCallCount('exist', 3);
        $notificationsManager->expectCallCount('remove', 3);
        $user->expectNever('getName');
    }

    function testRemove_monitoringSuccess() {
        $controller = new MockDocman_Controller();
        $controller->setReturnValue('userCanManage', true);
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $user = new MockUser();
        $userManager->setReturnValue('getUserById', $user);
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('exist', true);
        $notificationsManager->setReturnValue('remove', true);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->setReturnValue('_getUserManagerInstance', $userManager);
        $params['listeners_to_delete'] = array(1, 2, 3);
        $params['item'] = new MockDocman_Item();
        $actions->remove_monitoring($params);
        $controller->expectOnce('userCanManage');
        $userManager->expectCallCount('getUserById', 3);
        $notificationsManager->expectCallCount('exist', 3);
        $notificationsManager->expectCallCount('remove', 6);
        $user->expectCallCount('getName', 3);
    }

}
?>
