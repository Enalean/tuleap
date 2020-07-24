<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// Make easier the navigation in IDE between the main class and this class
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Docman_ActionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCannotDeleteVersionOnNonFile(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = \Mockery::spy(\Docman_Controller::class);
        $ctrl->feedback = \Mockery::spy(\Feedback::class);
        // Test log message
        $ctrl->feedback->shouldReceive('log')->with('error', 'Cannot delete a version on something that is not a file.')->once();

        // Setup of the test
        $actions = \Mockery::mock(\Docman_Actions::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $ctrl->request = \Mockery::spy(\HTTPRequest::class);
        $ctrl->request->shouldReceive('get')->with('group_id')->andReturns('102');
        $ctrl->request->shouldReceive('get')->with('id')->andReturns('344');
        $ctrl->request->shouldReceive('get')->with('version')->andReturns('1');
        $ctrl->request->shouldReceive('valid')->andReturns(true);
        $actions->_controler = $ctrl;

        $item = \Mockery::spy(\Docman_Folder::class);
        $if   = \Mockery::spy(\Docman_ItemFactory::class);
        $if->shouldReceive('getItemFromDb')->with(344)->once()->andReturns($item);
        $if->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $actions->shouldReceive('_getItemFactory')->with(102)->once()->andReturns($if);

        $actions->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    public function testCanDeleteVersionOfFile(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = \Mockery::spy(\Docman_Controller::class);
        $ctrl->feedback = \Mockery::spy(\Feedback::class);
        // Test log message
        $ctrl->feedback->shouldReceive('log')->with('info', 'Version 1 (label 5) successfully deleted')->once();

        // Setup of the test
        $actions = \Mockery::mock(\Docman_Actions::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $ctrl->request = \Mockery::spy(\HTTPRequest::class);
        $ctrl->request->shouldReceive('get')->with('group_id')->andReturns('102');
        $ctrl->request->shouldReceive('get')->with('id')->andReturns('344');
        $ctrl->request->shouldReceive('get')->with('version')->andReturns('1');
        $ctrl->request->shouldReceive('valid')->andReturns(true);
        $actions->_controler = $ctrl;

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('accept')->andReturns(true);

        $if = \Mockery::spy(\Docman_ItemFactory::class);
        $if->shouldReceive('getItemFromDb')->with(344)->andReturns($item);
        $if->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->shouldReceive('_getItemFactory')->with(102)->once()->andReturns($if);

        $v1 = \Mockery::spy(\Docman_Version::class);
        $v1->shouldReceive('getNumber')->andReturns(0);
        $v1->shouldReceive('getLabel')->andReturns('label 4');
        $v2 = \Mockery::spy(\Docman_Version::class);
        $v2->shouldReceive('getNumber')->andReturns(1);
        $v2->shouldReceive('getLabel')->andReturns('label 5');
        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [$v1, $v2]]);
        $actions->shouldReceive('_getVersionFactory')->andReturns($vf);

        $actions->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    public function testCannotDeleteLastVersion(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = \Mockery::spy(\Docman_Controller::class);
        $ctrl->feedback = \Mockery::spy(\Feedback::class);
        // Test log message
        $ctrl->feedback->shouldReceive('log')->with('error', 'Cannot delete last version of a file. If you want to continue, please delete the document itself.')->once();

        // Setup of the test
        $actions = \Mockery::mock(\Docman_Actions::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $ctrl->request = \Mockery::spy(\HTTPRequest::class);
        $ctrl->request->shouldReceive('get')->with('group_id')->andReturns('102');
        $ctrl->request->shouldReceive('get')->with('id')->andReturns('344');
        $ctrl->request->shouldReceive('get')->with('version')->andReturns('1');
        $ctrl->request->shouldReceive('valid')->andReturns(true);
        $actions->_controler = $ctrl;

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('accept')->andReturns(true);

        $if = \Mockery::spy(\Docman_ItemFactory::class);
        $if->shouldReceive('getItemFromDb')->with(344)->andReturns($item);
        $if->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->shouldReceive('_getItemFactory')->with(102)->once()->andReturns($if);

        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [M::mock(Docman_Version::class)]]);
        $actions->shouldReceive('_getVersionFactory')->andReturns($vf);

        $actions->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    public function testCannotDeleteNonExistantVersion(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = \Mockery::spy(\Docman_Controller::class);
        $ctrl->feedback = \Mockery::spy(\Feedback::class);
        // Test log message
        $ctrl->feedback->shouldReceive('log')->with('error', 'Cannot delete a version that doesn\'t exist.')->once();

        // Setup of the test
        $actions = \Mockery::mock(\Docman_Actions::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $ctrl->request = \Mockery::spy(\HTTPRequest::class);
        $ctrl->request->shouldReceive('get')->with('group_id')->andReturns('102');
        $ctrl->request->shouldReceive('get')->with('id')->andReturns('344');
        $ctrl->request->shouldReceive('get')->with('version')->andReturns('1');
        $ctrl->request->shouldReceive('valid')->andReturns(true);
        $actions->_controler = $ctrl;

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('accept')->never();

        $if = \Mockery::spy(\Docman_ItemFactory::class);
        $if->shouldReceive('getItemFromDb')->with(344)->andReturns($item);
        $if->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->shouldReceive('_getItemFactory')->with(102)->once()->andReturns($if);

        $v1 = \Mockery::spy(\Docman_Version::class);
        $v1->shouldReceive('getNumber')->andReturns(0);
        $v1->shouldReceive('getLabel')->andReturns('label 4');
        $v2 = \Mockery::spy(\Docman_Version::class);
        $v2->shouldReceive('getNumber')->andReturns(2);
        $v2->shouldReceive('getLabel')->andReturns('label 5');
        $vf = M::mock(Docman_VersionFactory::class, ['getAllVersionForItem' => [$v1, $v2]]);
        $actions->shouldReceive('_getVersionFactory')->andReturns($vf);

        $actions->shouldReceive('_getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Run test
        $actions->deleteVersion();
    }

    public function testRemoveMonitoringNothingToDelete(): void
    {
        $notificationsManager                  = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller                            = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback                  = \Mockery::spy(\Feedback::class);
        $actions                               = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = true;
        $params['listeners_ugroups_to_delete'] = true;
        $params['item']                        = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->shouldReceive('removeUser')->never();
    }

    public function testRemoveMonitoringNotifDoesNotExist(): void
    {
        $controller           = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback = \Mockery::spy(\Feedback::class);
        $user1                = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(123);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(132);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getId')->andReturns(133);
        $user3->shouldReceive('getName')->andReturns('Charlie');
        $controller->feedback->shouldReceive('log')->with('warning', 'Monitoring was not active for user "Carol"')->ordered();
        $controller->feedback->shouldReceive('log')->with('warning', 'Monitoring was not active for user "Carlos"')->ordered();
        $controller->feedback->shouldReceive('log')->with('warning', 'Monitoring was not active for user "Charlie"')->ordered();
        $notificationsManager                  = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager      = $notificationsManager;
        $actions                               = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $params['item']                        = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->shouldReceive('userExists')->with(123)->andReturns(false);
        $notificationsManager->shouldReceive('removeUser')->never();
    }

    public function testRemoveMonitoringError(): void
    {
        $controller           = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback = \Mockery::spy(\Feedback::class);
        $userManager          = \Mockery::spy(\UserManager::class);
        $user1                = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(123);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(132);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getId')->andReturns(133);
        $user3->shouldReceive('getName')->andReturns('Charlie');
        $controller->feedback->shouldReceive('log')->with('error', 'Unable to remove monitoring for user "Carol"')->ordered();
        $controller->feedback->shouldReceive('log')->with('error', 'Unable to remove monitoring for user "Carlos"')->ordered();
        $controller->feedback->shouldReceive('log')->with('error', 'Unable to remove monitoring for user "Charlie"')->ordered();
        $notificationsManager                  = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager      = $notificationsManager;
        $actions                               = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $item                                  = new Docman_Item();
        $item->setId(10);
        $params['item'] = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->times(3)->andReturns(true);
        $notificationsManager->shouldReceive('removeUser')->times(3)->andReturns(false);
        $actions->update_monitoring($params);
    }

    public function testRemoveMonitoringSuccess(): void
    {
        $controller           = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback = \Mockery::spy(\Feedback::class);
        $userManager          = \Mockery::spy(\UserManager::class);
        $user1                = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(123);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(132);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getId')->andReturns(133);
        $user3->shouldReceive('getName')->andReturns('Charlie');
        $controller->feedback->shouldReceive('log')->with('info', 'Removed monitoring for user(s) "Carol"')->once();
        $notificationsManager             = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler              = $controller;
        $actions->event_manager           = \Mockery::spy(EventManager::class);
        $actions->shouldReceive('_getUserManagerInstance')->andReturns($userManager);
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $params['item']                        = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->times(3)->andReturns(true);
        $notificationsManager->shouldReceive('removeUser')->times(6)->andReturns(true);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringNoOneToAdd(): void
    {
        $controller                 = \Mockery::spy(\Docman_Controller::class);
        $notificationsManager       = \Mockery::spy(\Docman_NotificationsManager::class);
        $actions                    = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler        = $controller;
        $params['listeners_to_add'] = true;
        $params['item']             = new Docman_Item();
        $actions->update_monitoring($params);
        $notificationsManager->shouldReceive('addUser')->never();
    }

    public function testAddMonitoringNotifAlreadyExist(): void
    {
        $controller                       = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback             = \Mockery::spy(\Feedback::class);
        $notificationsManager             = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $docmanPermissionsManager         = \Mockery::spy(\Docman_PermissionsManager::class);
        $actions->shouldReceive('_getDocmanPermissionsManagerInstance')->andReturns($docmanPermissionsManager);
        $actions->_controler = $controller;
        $user1               = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user1->shouldReceive('getId')->andReturns(1);
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $user2->shouldReceive('getId')->andReturns(2);
        $controller->feedback->shouldReceive('log')->with('warning', 'Monitoring for user(s) "Carol" already exists')->ordered();
        $controller->feedback->shouldReceive('log')->with('warning', 'Monitoring for user(s) "Carlos" already exists')->ordered();
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->times(2)->andReturns(true);
        $notificationsManager->shouldReceive('addUser')->never();
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringError(): void
    {
        $controller                       = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback             = \Mockery::spy(\Feedback::class);
        $notificationsManager             = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler              = $controller;
        $docmanPermissionsManager         = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionsManager->shouldReceive('userCanRead')->andReturns(true);
        $actions->shouldReceive('_getDocmanPermissionsManagerInstance')->andReturns($docmanPermissionsManager);
        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(123);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(132);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $controller->feedback->shouldReceive('log')->with('error', 'Monitoring for user(s) "Carol" has not been added')->ordered();
        $controller->feedback->shouldReceive('log')->with('error', 'Monitoring for user(s) "Carlos" has not been added')->ordered();
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->times(2)->andReturns(false);
        $notificationsManager->shouldReceive('addUser')->times(2)->andReturns(false);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringNoUserPermissions(): void
    {
        $controller                       = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback             = \Mockery::spy(\Feedback::class);
        $notificationsManager             = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler              = $controller;
        $docmanPermissionsManager         = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionsManager->shouldReceive('userCanRead')->once()->andReturns(true);
        $docmanPermissionsManager->shouldReceive('userCanRead')->once()->andReturns(false);
        $actions->shouldReceive('_getDocmanPermissionsManagerInstance')->andReturns($docmanPermissionsManager);
        $actions->event_manager = \Mockery::spy(EventManager::class);
        $user1                  = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(123);
        $user1->shouldReceive('getName')->andReturns('Carol');
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(132);
        $user2->shouldReceive('getName')->andReturns('Carlos');
        $controller->feedback->shouldReceive('log')->with('warning', 'Insufficient permissions for user(s) "Carlos"')->ordered();
        $controller->feedback->shouldReceive('log')->with('info', 'Le monitoring a été ajouté pour le(s) utilisateur(s) "Carol"')->ordered();
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->times(2)->andReturns(false);
        $notificationsManager->shouldReceive('addUser')->once()->andReturns(true);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringSuccess(): void
    {
        $controller           = \Mockery::spy(\Docman_Controller::class);
        $controller->feedback = \Mockery::spy(\Feedback::class);
        $user                 = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(123);
        $user->shouldReceive('getName')->andReturns('Carol');
        $controller->feedback->shouldReceive('log')->with('info', 'Monitoring for user(s) "Carol" has been added')->once();
        $notificationsManager             = \Mockery::spy(\Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = \Mockery::mock(\Docman_Actions::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $actions->_controler              = $controller;
        $actions->event_manager           = \Mockery::spy(EventManager::class);
        $docmanPermissionsManager         = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionsManager->shouldReceive('userCanRead')->andReturns(true);
        $actions->shouldReceive('_getDocmanPermissionsManagerInstance')->andReturns($docmanPermissionsManager);
        $params['listeners_users_to_add'] = [$user];
        $params['item']                   = new Docman_Item();
        $notificationsManager->shouldReceive('userExists')->once()->andReturns(false);
        $notificationsManager->shouldReceive('addUser')->once()->andReturns(true);
        $actions->update_monitoring($params);
    }
}
