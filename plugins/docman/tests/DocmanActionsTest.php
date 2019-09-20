<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Mockery as M;

require_once 'bootstrap.php';

Mock::generatePartial('Docman_Actions', 'Docman_ActionsTest', array('_getItemFactory',
                                                                   '_getFileStorage',
                                                                   '_getActionsDeleteVisitor',
                                                                   '_getEventManager',
                                                                   '_getVersionFactory',
                                                                   '_getDocmanPermissionsManagerInstance',
                                                                   '_getUserManagerInstance'));


Mock::generate('Docman_Controller');

Mock::generate('Docman_PermissionsManager');



Mock::generate('HTTPRequest');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_Folder');
Mock::generate('Docman_File');
Mock::generate('Feedback');
Mock::generate('Docman_Version');
Mock::generate('Docman_Item');
Mock::generate('Docman_NotificationsManager');


Mock::generate('BaseLanguage');

Mock::generate('UserManager');
Mock::generate('PFUser');

class DocmanActionsTest extends TuleapTestCase
{

    function testCannotDeleteVersionOnNonFile()
    {
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

        $actions->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    function testCanDeleteVersionOfFile()
    {
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

        $v1 = new MockDocman_Version($this);
        $v1->setReturnValue('getNumber', 0);
        $v1->setReturnValue('getLabel', 'label 4');
        $v2 = new MockDocman_Version($this);
        $v2->setReturnValue('getNumber', 1);
        $v2->setReturnValue('getLabel', 'label 5');
        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [$v1, $v2]]);
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    function testCannotDeleteLastVersion()
    {
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

        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [M::mock(Docman_Version::class)]]);
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    function testCannotDeleteNonExistantVersion()
    {
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

        $v1 = new MockDocman_Version($this);
        $v1->setReturnValue('getNumber', 0);
        $v1->setReturnValue('getLabel', 'label 4');
        $v2 = new MockDocman_Version($this);
        $v2->setReturnValue('getNumber', 2);
        $v2->setReturnValue('getLabel', 'label 5');
        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [$v1, $v2]]);
        $actions->setReturnValue('_getVersionFactory', $vf);

        $actions->setReturnValue('_getEventManager', \Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    function testRemove_monitoringNothingToDelete()
    {
        $notificationsManager                  = new MockDocman_NotificationsManager();
        $controller                            = new MockDocman_Controller();
        $controller->feedback                  = new MockFeedback();
        $actions                               = new Docman_ActionsTest();
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = true;
        $params['listeners_ugroups_to_delete'] = true;
        $params['item']                        = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectNever('removeUser');
    }

    function testRemove_monitoringNotifDoesNotExist()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 123);
        $user1->setReturnValue('getName', 'Carol');
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 132);
        $user2->setReturnValue('getName', 'Carlos');
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 133);
        $user3->setReturnValue('getName', 'Charlie');
        $controller->feedback->expectAt(0, 'log', array('warning', '*'));
        $GLOBALS['Language']->expectAt(0, 'getText', array('plugin_docman', 'notifications_not_present_user', array($user1->getName())));
        $controller->feedback->expectAt(1, 'log', array('warning', '*'));
        $GLOBALS['Language']->expectAt(1, 'getText', array('plugin_docman', 'notifications_not_present_user', array($user2->getName())));
        $controller->feedback->expectAt(2, 'log', array('warning', '*'));
        $GLOBALS['Language']->expectAt(2, 'getText', array('plugin_docman', 'notifications_not_present_user', array($user3->getName())));
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', false);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $params['listeners_users_to_delete']   = array($user1, $user2, $user3);
        $params['listeners_ugroups_to_delete'] = array();
        $params['item'] = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 3);
        $notificationsManager->expectNever('removeUser');
    }

    function testRemove_monitoringError()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 123);
        $user1->setReturnValue('getName', 'Carol');
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 132);
        $user2->setReturnValue('getName', 'Carlos');
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 133);
        $user3->setReturnValue('getName', 'Charlie');
        $controller->feedback->expectAt(0, 'log', array('error', '*'));
        $GLOBALS['Language']->expectAt(0, 'getText', array('plugin_docman', 'notifications_not_removed_user', array($user1->getName())));
        $controller->feedback->expectAt(1, 'log', array('error', '*'));
        $GLOBALS['Language']->expectAt(1, 'getText', array('plugin_docman', 'notifications_not_removed_user', array($user2->getName())));
        $controller->feedback->expectAt(2, 'log', array('error', '*'));
        $GLOBALS['Language']->expectAt(2, 'getText', array('plugin_docman', 'notifications_not_removed_user', array($user3->getName())));
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', true);
        $notificationsManager->setReturnValue('removeUser', false);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $params['listeners_users_to_delete']   = array($user1, $user2, $user3);
        $params['listeners_ugroups_to_delete'] = array();
        $params['item'] = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 3);
        $notificationsManager->expectCallCount('removeUser', 3);
    }

    function testRemove_monitoringSuccess()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $userManager = new MockUserManager();
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 123);
        $user1->setReturnValue('getName', 'Carol');
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 132);
        $user2->setReturnValue('getName', 'Carlos');
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 133);
        $user3->setReturnValue('getName', 'Charlie');
        $controller->feedback->expectOnce('log', array('info', '*'));
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'notifications_removed_user', array('Carol,Carlos,Charlie')));
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', true);
        $notificationsManager->setReturnValue('removeUser', true);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->event_manager = \Mockery::spy(EventManager::class);
        $actions->setReturnValue('_getUserManagerInstance', $userManager);
        $params['listeners_users_to_delete']   = array($user1, $user2, $user3);
        $params['listeners_ugroups_to_delete'] = array();
        $params['item'] = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 3);
        $notificationsManager->expectCallCount('removeUser', 6);
    }

    function testAdd_monitoringNoOneToAdd()
    {
        $controller                 = new MockDocman_Controller();
        $notificationsManager       = new MockDocman_NotificationsManager();
        $actions                    = new Docman_ActionsTest();
        $actions->_controler        = $controller;
        $params['listeners_to_add'] = true;
        $params['item']             = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectNever('addUser');
    }

    function testAdd_monitoringNotifAlreadyExist()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', true);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $user1 = mock('PFUser');
        $user1->setReturnValue('getName', 'Carol');
        $user1->setReturnValue('getId', 1);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getName', 'Carlos');
        $user2->setReturnValue('getId', 2);
        $controller->feedback->expectAt(0, 'log', array('warning', '*'));
        $controller->feedback->expectAt(1, 'log', array('warning', '*'));
        $GLOBALS['Language']->expectAt(0, 'getText', array('plugin_docman', 'notifications_already_exists_user', array('Carol')));
        $GLOBALS['Language']->expectAt(1, 'getText', array('plugin_docman', 'notifications_already_exists_user', array('Carlos')));
        $params['listeners_users_to_add'] = array($user1, $user2);
        $params['item']                   = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 2);
        $notificationsManager->expectNever('addUser');
    }

    function testAdd_monitoringError()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', false);
        $notificationsManager->setReturnValue('addUser', false);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $docmanPermissionsManager = new MockDocman_PermissionsManager();
        $docmanPermissionsManager->setReturnValue('userCanRead', true);
        $actions->setReturnValue('_getDocmanPermissionsManagerInstance', $docmanPermissionsManager);
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 123);
        $user1->setReturnValue('getName', 'Carol');
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 132);
        $user2->setReturnValue('getName', 'Carlos');
        $controller->feedback->expectAt(0, 'log', array('error', '*'));
        $GLOBALS['Language']->expectAt(0, 'getText', array('plugin_docman', 'notifications_not_added_user', array($user1->getName())));
        $controller->feedback->expectAt(1, 'log', array('error', '*'));
        $GLOBALS['Language']->expectAt(1, 'getText', array('plugin_docman', 'notifications_not_added_user', array($user2->getName())));
        $params['listeners_users_to_add'] = array($user1, $user2);
        $params['item']                   = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 2);
        $notificationsManager->expectCallCount('addUser', 2);
    }

    function testAdd_monitoringNoUserPermissions()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', false);
        $notificationsManager->setReturnValue('addUser', true);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $docmanPermissionsManager = new MockDocman_PermissionsManager();
        $docmanPermissionsManager->setReturnValueAt(0, 'userCanRead', true);
        $docmanPermissionsManager->setReturnValueAt(1, 'userCanRead', false);
        $actions->setReturnValue('_getDocmanPermissionsManagerInstance', $docmanPermissionsManager);
        $actions->event_manager = \Mockery::spy(EventManager::class);
        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 123);
        $user1->setReturnValue('getName', 'Carol');
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 132);
        $user2->setReturnValue('getName', 'Carlos');
        $controller->feedback->expectAt(0, 'log', array('warning', '*'));
        $GLOBALS['Language']->expectAt(0, 'getText', array('plugin_docman', 'notifications_no_access_rights_user', array($user2->getName())));
        $controller->feedback->expectAt(1, 'log', array('info', '*'));
        $GLOBALS['Language']->expectAt(1, 'getText', array('plugin_docman', 'notifications_added_user', array($user1->getName())));
        $params['listeners_users_to_add'] = array($user1, $user2);
        $params['item']                   = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 2);
        $docmanPermissionsManager->expectCallCount('userCanRead', 2);
        $notificationsManager->expectCallCount('addUser', 1);
    }

    function testAdd_monitoringSuccess()
    {
        $controller = new MockDocman_Controller();
        $controller->feedback = new MockFeedback();
        $user = mock('PFUser');
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('getName', 'Carol');
        $controller->feedback->expectOnce('log', array('info', '*'));
        $GLOBALS['Language']->expectOnce('getText', array('plugin_docman', 'notifications_added_user', array($user->getName())));
        $notificationsManager = new MockDocman_NotificationsManager();
        $notificationsManager->setReturnValue('userExists', false);
        $notificationsManager->setReturnValue('addUser', true);
        $controller->notificationsManager = $notificationsManager;
        $actions = new Docman_ActionsTest();
        $actions->_controler = $controller;
        $actions->event_manager = \Mockery::spy(EventManager::class);
        $docmanPermissionsManager = new MockDocman_PermissionsManager();
        $docmanPermissionsManager->setReturnValue('userCanRead', true);
        $actions->setReturnValue('_getDocmanPermissionsManagerInstance', $docmanPermissionsManager);
        $params['listeners_users_to_add'] = array($user);
        $params['item']                   = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->expectCallCount('userExists', 1);
        $notificationsManager->expectCallCount('addUser', 1);
    }
}
