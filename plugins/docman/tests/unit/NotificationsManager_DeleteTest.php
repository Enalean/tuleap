<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 *  This file is a part of Tuleap.
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
 *
 */

require_once 'bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class NotificationsManager_DeleteTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /*
     * Test the case when deleting a docman item the notification mail
     * is sent to all monitoring users not only one of them
     */
    public function testStoreEventsDoNotOverrideUsers()
    {
        $listeningUsers = new ArrayIterator(array(array('user_id' => 1,
                                                        'item_id' => 1),
                                                  array('user_id' => 2,
                                                        'item_id' => 1),
                                                  array('user_id' => 3,
                                                        'item_id' => 1)));

        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getId')->andReturns(1);
        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getId')->andReturns(2);
        $user3 = \Mockery::spy(\PFUser::class);
        $user3->shouldReceive('getId')->andReturns(3);
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserById')->once()->andReturns($user1);
        $um->shouldReceive('getUserById')->once()->andReturns($user2);
        $um->shouldReceive('getUserById')->once()->andReturns($user3);

        $dpm = \Mockery::spy(\Docman_PermissionsManager::class);
        $dpm->shouldReceive('userCanRead')->andReturns(true);
        $dpm->shouldReceive('userCanAccess')->andReturns(true);

        $item = \Mockery::spy(\Docman_Item::class);
        $item->shouldReceive('getId')->andReturns(1);
        $params = array('item' => $item);

        $project                   = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $feedback                  = \Mockery::spy(\Feedback::class);
        $mail_builder              = \Mockery::spy(\MailBuilder::class);
        $notifications_dao         = \Mockery::spy(\Tuleap\Docman\Notifications\UsersToNotifyDao::class);
        $notified_people_retriever = \Mockery::spy(\Tuleap\Docman\Notifications\NotifiedPeopleRetriever::class);
        $notified_people_retriever->shouldReceive('getNotifiedUsers')->andReturns($listeningUsers);
        $users_remover   = \Mockery::spy(\Tuleap\Docman\Notifications\UsersUpdater::class);
        $ugroups_remover = \Mockery::spy(\Tuleap\Docman\Notifications\UgroupsUpdater::class);

        $notifications_manager = \Mockery::mock(\Docman_NotificationsManager_Delete::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $link_url_provider                  = Mockery::mock(ILinkUrlProvider::class);
        $notifications_manager->__construct(
            $project,
            $link_url_provider,
            $feedback,
            $mail_builder,
            $notifications_dao,
            \Mockery::spy(\Tuleap\Docman\Notifications\UsersRetriever::class),
            \Mockery::spy(\Tuleap\Docman\Notifications\UGroupsRetriever::class),
            $notified_people_retriever,
            $users_remover,
            $ugroups_remover
        );
        $notifications_manager->shouldReceive('_getUserManager')->andReturns($um);
        $notifications_manager->shouldReceive('_getPermissionsManager')->andReturns($dpm);
        $notifications_manager->shouldReceive('getUrlProvider')->andReturns($link_url_provider);
        $notifications_manager->_listeners = array();

        $notifications_manager->_storeEvents(1, 'removed', $params);

        $this->assertEquals($user1, $notifications_manager->_listeners[1]['user']);
        $this->assertEquals($user2, $notifications_manager->_listeners[2]['user']);
        $this->assertEquals($user3, $notifications_manager->_listeners[3]['user']);
    }
}
