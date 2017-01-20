<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

Mock::generatePartial('Docman_NotificationsManager_Delete', 'Docman_NotificationsManager_DeleteTestVersion',
    array(
        '_getPermissionsManager',
        '_getListeningUsers',
        '_getUserManager'
    )
);

Mock::generate('Docman_Item');

Mock::generate('UserManager');

Mock::generate('PFUser');

Mock::generate('Docman_PermissionsManager');

class NotificationsManager_DeleteTest extends TuleapTestCase {

    /*
     * Test the case when deleting a docman item the notification mail
     * is sent to all monitoring users not only one of them
     */
    function testStoreEventsDoNotOverrideUsers() {
        $listeningUsers = new ArrayIterator(array(array('user_id'   => 1,
                                                        'object_id' => 1),
                                                  array('user_id'   => 2,
                                                        'object_id' => 1),
                                                  array('user_id'   => 3,
                                                        'object_id' => 1)));

        $user1 = mock('PFUser');
        $user1->setReturnValue('getId', 1);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getId', 2);
        $user3 = mock('PFUser');
        $user3->setReturnValue('getId', 3);
        $um = new MockUserManager();
        $um->setReturnValueAt(0, 'getUserById', $user1);
        $um->setReturnValueAt(1, 'getUserById', $user2);
        $um->setReturnValueAt(2, 'getUserById', $user3);

        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanRead', true);
        $dpm->setReturnValue('userCanAccess', true);

        $item = new MockDocman_Item();
        $item->setReturnValue('getId', 1);
        $params = array('item' => $item);

        $nm = new Docman_NotificationsManager_DeleteTestVersion();
        $nm->setReturnValue('_getListeningUsers', $listeningUsers);
        $nm->setReturnValue('_getUserManager', $um);
        $nm->setReturnValue('_getPermissionsManager', $dpm);
        $nm->_listeners = array();

        $nm->_storeEvents(1, 'removed', $params);

        $this->assertEqual($user1, $nm->_listeners[1]['user']);
        $this->assertEqual($user2, $nm->_listeners[2]['user']);
        $this->assertEqual($user3, $nm->_listeners[3]['user']);
    }

}
