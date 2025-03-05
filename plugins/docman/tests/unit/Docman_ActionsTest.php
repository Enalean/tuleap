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

declare(strict_types=1);

namespace Tuleap\Docman;

use Codendi_Request;
use Docman_Actions;
use Docman_Controller;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_NotificationsManager;
use Docman_PermissionsManager;
use Docman_Version;
use Docman_VersionFactory;
use EventManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

// Make easier the navigation in IDE between the main class and this class
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_ActionsTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testCannotDeleteVersionOnNonFile(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = $this->createMock(Docman_Controller::class);
        $ctrl->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        // Test log message
        $ctrl->feedback->expects(self::once())->method('log')->with('error', 'Cannot delete a version on something that is not a file.');

        // Setup of the test
        $actions = $this->createPartialMock(Docman_Actions::class, [
            '_getItemFactory',
            '_getEventManager',
        ]);

        $ctrl->request       = new Codendi_Request(['group_id' => '102', 'id' => '344', 'version' => '1']);
        $actions->_controler = $ctrl;

        $item = new Docman_Folder();
        $if   = $this->createMock(Docman_ItemFactory::class);
        $if->expects(self::once())->method('getItemFromDb')->with(344)->willReturn($item);
        $if->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $actions->expects(self::once())->method('_getItemFactory')->with(102)->willReturn($if);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $actions->method('_getEventManager')->willReturn($event_manager);

        // Run test
        $actions->deleteVersion();
    }

    public function testCanDeleteVersionOfFile(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = $this->createMock(Docman_Controller::class);
        $ctrl->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        // Test log message
        $ctrl->feedback->expects(self::once())->method('log')->with('info', 'Version 1 (label 5) successfully deleted');

        // Setup of the test
        $actions = $this->createPartialMock(Docman_Actions::class, [
            '_getVersionFactory',
            '_getEventManager',
            '_getItemFactory',
        ]);

        $ctrl->request       = new Codendi_Request(['group_id' => '102', 'id' => '344', 'version' => '1']);
        $actions->_controler = $ctrl;
        $ctrl->method('getUser');

        $item = $this->createMock(Docman_File::class);
        $item->method('accept')->willReturn(true);

        $if = $this->createMock(Docman_ItemFactory::class);
        $if->method('getItemFromDb')->with(344)->willReturn($item);
        $if->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->expects(self::once())->method('_getItemFactory')->with(102)->willReturn($if);

        $v1 = new Docman_Version(['number' => 0, 'label' => 'label 4']);
        $v2 = new Docman_Version(['number' => 1, 'label' => 'label 5']);
        $vf = $this->createMock(Docman_VersionFactory::class);
        $vf->method('getAllVersionForItem')->willReturn([$v1, $v2]);
        $actions->method('_getVersionFactory')->willReturn($vf);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $actions->method('_getEventManager')->willReturn($event_manager);

        // Run test
        $actions->deleteVersion();
    }

    public function testCannotDeleteLastVersion(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an error and the error message is the right one
        $ctrl           = $this->createMock(Docman_Controller::class);
        $ctrl->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        // Test log message
        $ctrl->feedback->expects(self::once())->method('log')->with('error', 'Cannot delete last version of a file. If you want to continue, please delete the document itself.');

        // Setup of the test
        $actions = $this->createPartialMock(Docman_Actions::class, [
            '_getItemFactory',
            '_getVersionFactory',
            '_getEventManager',
        ]);

        $ctrl->request       = new Codendi_Request(['group_id' => '102', 'id' => '344', 'version' => '1']);
        $actions->_controler = $ctrl;

        $item = $this->createMock(Docman_File::class);
        $item->method('accept')->willReturn(true);

        $if = $this->createMock(Docman_ItemFactory::class);
        $if->method('getItemFromDb')->with(344)->willReturn($item);
        $if->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->expects(self::once())->method('_getItemFactory')->with(102)->willReturn($if);

        $vf = $this->createMock(Docman_VersionFactory::class);
        $vf->method('getAllVersionForItem')->willReturn([new Docman_Version()]);
        $actions->method('_getVersionFactory')->willReturn($vf);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $actions->method('_getEventManager')->willReturn($event_manager);

        // Run test
        $actions->deleteVersion();
    }

    public function testCannotDeleteNonExistantVersion(): void
    {
        // Definition acceptance criteria:
        // test is complete if there is an info flash message that tells version is deleted
        $ctrl           = $this->createMock(Docman_Controller::class);
        $ctrl->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        // Test log message
        $ctrl->feedback->expects(self::once())->method('log')->with('error', 'Cannot delete a version that doesn\'t exist.');

        // Setup of the test
        $actions = $this->createPartialMock(Docman_Actions::class, [
            '_getItemFactory',
            '_getVersionFactory',
            '_getEventManager',
        ]);

        $ctrl->request       = new Codendi_Request(['group_id' => '102', 'id' => '344', 'version' => '1']);
        $actions->_controler = $ctrl;

        $item = new Docman_File();

        $if = $this->createMock(Docman_ItemFactory::class);
        $if->method('getItemFromDb')->with(344)->willReturn($item);
        $if->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $actions->expects(self::once())->method('_getItemFactory')->with(102)->willReturn($if);

        $v1 = new Docman_Version(['number' => 0, 'label' => 'label 4']);
        $v2 = new Docman_Version(['number' => 2, 'label' => 'label 5']);
        $vf = $this->createMock(Docman_VersionFactory::class);
        $vf->method('getAllVersionForItem')->willReturn([$v1, $v2]);
        $actions->method('_getVersionFactory')->willReturn($vf);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $actions->method('_getEventManager')->willReturn($event_manager);

        // Run test
        $actions->deleteVersion();
    }

    public function testRemoveMonitoringNothingToDelete(): void
    {
        $notificationsManager                  = $this->createMock(Docman_NotificationsManager::class);
        $controller                            = $this->createMock(Docman_Controller::class);
        $controller->feedback                  = $this->createMock(ResponseFeedbackWrapper::class);
        $actions                               = $this->createPartialMock(Docman_Actions::class, []);
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = true;
        $params['listeners_ugroups_to_delete'] = true;
        $params['item']                        = new Docman_Item();
        $notificationsManager->expects(self::never())->method('removeUser');
        $actions->update_monitoring($params);
    }

    public function testRemoveMonitoringNotifDoesNotExist(): void
    {
        $controller           = $this->createMock(Docman_Controller::class);
        $controller->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        $user1                = UserTestBuilder::aUser()->withId(123)->withUserName('Carol')->build();
        $user2                = UserTestBuilder::aUser()->withId(132)->withUserName('Carlos')->build();
        $user3                = UserTestBuilder::aUser()->withId(133)->withUserName('Charlie')->build();
        $matcher              = $this->exactly(3);
        $controller->feedback->expects($matcher)->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Monitoring was not active for user "Carol"', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Monitoring was not active for user "Carlos"', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Monitoring was not active for user "Charlie"', $parameters[1]);
            }
        });
        $notificationsManager                  = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager      = $notificationsManager;
        $actions                               = $this->createPartialMock(Docman_Actions::class, []);
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $params['item']                        = new Docman_Item();
        $matcher                               = $this->exactly(3);
        $notificationsManager->expects($matcher)->method('userExists')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(123, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(132, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame(133, $parameters[0]);
            }
            return false;
        });
        $notificationsManager->expects(self::never())->method('removeUser');
        $actions->update_monitoring($params);
    }

    public function testRemoveMonitoringError(): void
    {
        $controller           = $this->createMock(Docman_Controller::class);
        $controller->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        $user1                = UserTestBuilder::aUser()->withId(123)->withUserName('Carol')->build();
        $user2                = UserTestBuilder::aUser()->withId(132)->withUserName('Carlos')->build();
        $user3                = UserTestBuilder::aUser()->withId(133)->withUserName('Charlie')->build();
        $matcher              = $this->exactly(3);
        $controller->feedback->expects($matcher)->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('error', $parameters[0]);
                self::assertSame('Unable to remove monitoring for user "Carol"', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('error', $parameters[0]);
                self::assertSame('Unable to remove monitoring for user "Carlos"', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                self::assertSame('error', $parameters[0]);
                self::assertSame('Unable to remove monitoring for user "Charlie"', $parameters[1]);
            }
        });
        $notificationsManager                  = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager      = $notificationsManager;
        $actions                               = $this->createPartialMock(Docman_Actions::class, []);
        $actions->_controler                   = $controller;
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $item                                  = new Docman_Item();
        $item->setId(10);
        $params['item'] = new Docman_Item();
        $notificationsManager->expects(self::exactly(3))->method('userExists')->willReturn(true);
        $notificationsManager->expects(self::exactly(3))->method('removeUser')->willReturn(false);
        $actions->update_monitoring($params);
    }

    public function testRemoveMonitoringSuccess(): void
    {
        $controller           = $this->createMock(Docman_Controller::class);
        $controller->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        $userManager          = $this->createMock(UserManager::class);
        $user1                = UserTestBuilder::aUser()->withId(123)->withUserName('Carol')->build();
        $user2                = UserTestBuilder::aUser()->withId(132)->withUserName('Carlos')->build();
        $user3                = UserTestBuilder::aUser()->withId(133)->withUserName('Charlie')->build();
        $controller->feedback->expects(self::once())->method('log')->with('info', 'Removed monitoring for user(s) "Carol"');
        $notificationsManager             = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = $this->createPartialMock(Docman_Actions::class, ['_getUserManagerInstance']);
        $actions->_controler              = $controller;
        $actions->event_manager           = $this->createMock(EventManager::class);
        $actions->event_manager->method('processEvent');
        $actions->method('_getUserManagerInstance')->willReturn($userManager);
        $params['listeners_users_to_delete']   = [$user1, $user2, $user3];
        $params['listeners_ugroups_to_delete'] = [];
        $params['item']                        = new Docman_Item();
        $notificationsManager->expects(self::exactly(3))->method('userExists')->willReturn(true);
        $notificationsManager->expects(self::exactly(6))->method('removeUser')->willReturn(true);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringNoOneToAdd(): void
    {
        $controller                 = $this->createMock(Docman_Controller::class);
        $notificationsManager       = $this->createMock(Docman_NotificationsManager::class);
        $actions                    = $this->createPartialMock(Docman_Actions::class, []);
        $actions->_controler        = $controller;
        $params['listeners_to_add'] = true;
        $params['item']             = new Docman_Item();
        $notificationsManager->expects(self::never())->method('addUser');
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringNotifAlreadyExist(): void
    {
        $controller                       = $this->createMock(Docman_Controller::class);
        $controller->feedback             = $this->createMock(ResponseFeedbackWrapper::class);
        $notificationsManager             = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = $this->createPartialMock(Docman_Actions::class, ['_getDocmanPermissionsManagerInstance']);
        $docmanPermissionsManager         = $this->createMock(Docman_PermissionsManager::class);
        $actions->method('_getDocmanPermissionsManagerInstance')->willReturn($docmanPermissionsManager);
        $actions->_controler = $controller;
        $user1               = UserTestBuilder::aUser()->withId(1)->withUserName('Carol')->build();
        $user2               = UserTestBuilder::aUser()->withId(2)->withUserName('Carlos')->build();
        $matcher             = $this->exactly(2);
        $controller->feedback->expects($matcher)->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Monitoring for user(s) "Carol" already exists', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Monitoring for user(s) "Carlos" already exists', $parameters[1]);
            }
        });
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->expects(self::exactly(2))->method('userExists')->willReturn(true);
        $notificationsManager->expects(self::never())->method('addUser');
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringError(): void
    {
        $controller                       = $this->createMock(Docman_Controller::class);
        $controller->feedback             = $this->createMock(ResponseFeedbackWrapper::class);
        $notificationsManager             = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = $this->createPartialMock(Docman_Actions::class, ['_getDocmanPermissionsManagerInstance']);
        $actions->_controler              = $controller;
        $docmanPermissionsManager         = $this->createMock(Docman_PermissionsManager::class);
        $docmanPermissionsManager->method('userCanRead')->willReturn(true);
        $actions->method('_getDocmanPermissionsManagerInstance')->willReturn($docmanPermissionsManager);
        $user1   = UserTestBuilder::aUser()->withId(123)->withUserName('Carol')->build();
        $user2   = UserTestBuilder::aUser()->withId(132)->withUserName('Carlos')->build();
        $matcher = $this->exactly(2);
        $controller->feedback->expects($matcher)->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('error', $parameters[0]);
                self::assertSame('Monitoring for user(s) "Carol" has not been added', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('error', $parameters[0]);
                self::assertSame('Monitoring for user(s) "Carlos" has not been added', $parameters[1]);
            }
        });
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->expects(self::exactly(2))->method('userExists')->willReturn(false);
        $notificationsManager->expects(self::exactly(2))->method('addUser')->willReturn(false);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringNoUserPermissions(): void
    {
        $controller                       = $this->createMock(Docman_Controller::class);
        $controller->feedback             = $this->createMock(ResponseFeedbackWrapper::class);
        $notificationsManager             = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = $this->createPartialMock(Docman_Actions::class, ['_getDocmanPermissionsManagerInstance']);
        $actions->_controler              = $controller;
        $docmanPermissionsManager         = $this->createMock(Docman_PermissionsManager::class);
        $docmanPermissionsManager->expects(self::exactly(2))->method('userCanRead')->willReturnOnConsecutiveCalls(true, false);
        $actions->method('_getDocmanPermissionsManagerInstance')->willReturn($docmanPermissionsManager);
        $actions->event_manager = $this->createMock(EventManager::class);
        $actions->event_manager->method('processEvent');
        $user1   = UserTestBuilder::aUser()->withId(123)->withUserName('Carol')->build();
        $user2   = UserTestBuilder::aUser()->withId(132)->withUserName('Carlos')->build();
        $matcher = $this->exactly(2);
        $controller->feedback->expects($matcher)->method('log')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('warning', $parameters[0]);
                self::assertSame('Insufficient permissions for user(s) "Carlos"', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('info', $parameters[0]);
                self::assertSame('Monitoring for user(s) "Carol" has been added', $parameters[1]);
            }
        });
        $params['listeners_users_to_add'] = [$user1, $user2];
        $params['item']                   = new Docman_Item();
        $notificationsManager->expects(self::exactly(2))->method('userExists')->willReturn(false);
        $notificationsManager->expects(self::once())->method('addUser')->willReturn(true);
        $actions->update_monitoring($params);
    }

    public function testAddMonitoringSuccess(): void
    {
        $controller           = $this->createMock(Docman_Controller::class);
        $controller->feedback = $this->createMock(ResponseFeedbackWrapper::class);
        $user                 = UserTestBuilder::anActiveUser()->withId(123)->withUserName('Carol')->build();
        $controller->feedback->expects(self::once())->method('log')->with('info', 'Monitoring for user(s) "Carol" has been added');
        $notificationsManager             = $this->createMock(Docman_NotificationsManager::class);
        $controller->notificationsManager = $notificationsManager;
        $actions                          = $this->createPartialMock(Docman_Actions::class, ['_getDocmanPermissionsManagerInstance']);
        $actions->_controler              = $controller;
        $actions->event_manager           = $this->createMock(EventManager::class);
        $actions->event_manager->method('processEvent');
        $docmanPermissionsManager = $this->createMock(Docman_PermissionsManager::class);
        $docmanPermissionsManager->method('userCanRead')->willReturn(true);
        $actions->method('_getDocmanPermissionsManagerInstance')->willReturn($docmanPermissionsManager);
        $params['listeners_users_to_add'] = [$user];
        $params['item']                   = new Docman_Item();
        $notificationsManager->expects(self::once())->method('userExists')->willReturn(false);
        $notificationsManager->expects(self::once())->method('addUser')->willReturn(true);
        $actions->update_monitoring($params);
    }
}
