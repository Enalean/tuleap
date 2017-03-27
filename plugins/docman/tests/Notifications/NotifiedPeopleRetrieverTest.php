<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Docman_ItemFactory;
use Project;
use TuleapTestCase;
use UGroupManager;

require_once __DIR__ . '/../bootstrap.php';

class NotifiedPeopleRetrieverTest extends TuleapTestCase
{
    /** @var  UsersRetriever */
    private $retriever;
    /** @var  UsersToNotifyDao */
    private $notified_users_dao;
    /** @var  UgroupsToNotifyDao */
    private $notified_ugroups_dao;
    /** @var  UGroupManager */
    private $ugroup_manager;
    /** @var  Docman_ItemFactory */
    private $item_factory;
    /** @var  Project */
    private $project;

    private $user_id;
    private $item_id;
    private $parent_item_id;

    public function setUp()
    {
        parent::setUp();

        $this->project = aMockProject()->build();

        $this->notified_users_dao   = mock('Tuleap\Docman\Notifications\UsersToNotifyDao');
        $this->notified_ugroups_dao = mock('Tuleap\Docman\Notifications\UgroupsToNotifyDao');
        $this->ugroup_manager       = mock('UgroupManager');
        $this->item_factory   = mock('Docman_ItemFactory');

        $this->retriever = new NotifiedPeopleRetriever(
            $this->notified_users_dao,
            $this->notified_ugroups_dao,
            $this->item_factory,
            $this->ugroup_manager
        );

        $this->user_id        = 105;
        $this->item_id        = 66;
        $this->parent_item_id = 65;

        $user = aUser()->withId($this->user_id)->build();
        $custom_ugroup = aMockUGroup()->withMembers(array($user))->build();
        stub($this->ugroup_manager)->getUGroup($this->project, 169)->returns($custom_ugroup);
    }

    public function itNotifiesUsersListeningToItem()
    {
        $this->userDaoReturnsItemIdAndUser105($this->item_id);
        $this->ugroupDaoReturnsFalse($this->item_id);

        $this->itemExistsinDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => $this->item_id,
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION
                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    public function itNotifiesUsersListeningToParentOfItem()
    {
        $this->userDaoReturnsFalse($this->item_id);
        $this->ugroupDaoReturnsFalse($this->item_id);
        $this->userDaoReturnsItemIdAndUser105ByCascade($this->parent_item_id);
        $this->ugroupDaoReturnsFalse($this->parent_item_id);

        $this->itemExistsinDb();
        $this->parentItemExistsInDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => '65',
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,

                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    public function itDoesNotNotifyTwiceTheSameUser()
    {
        $this->userDaoReturnsItemIdAndUser105($this->item_id);
        $this->ugroupDaoReturnsFalse($this->item_id);
        $this->userDaoReturnsItemIdAndUser105ByCascade($this->parent_item_id);
        $this->ugroupDaoReturnsFalse($this->parent_item_id);

        $this->itemExistsinDb();
        $this->parentItemExistsInDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => '65',
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE,

                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    public function itNotifiesUgroupMembersListeningToItem()
    {
        $this->userDaoReturnsFalse($this->item_id);
        $this->ugroupDaoReturnsUgroup169($this->item_id);

        $this->itemExistsinDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => $this->item_id,
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION
                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    public function itNotifiesUgroupMembersListeningToParentOfItem()
    {
        $this->userDaoReturnsFalse($this->item_id);
        $this->ugroupDaoReturnsFalse($this->item_id);
        $this->userDaoReturnsFalseByCascade($this->parent_item_id);
        $this->ugroupDaoReturnsUgroup169ByCascade($this->parent_item_id);

        $this->itemExistsinDb();
        $this->parentItemExistsInDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => '65',
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE
                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    public function itDoesNotNotifyTwiceTheSameUserInUgroupAndInList()
    {
        $this->userDaoReturnsItemIdAndUser105($this->item_id);
        $this->ugroupDaoReturnsUgroup169($this->item_id);

        $this->itemExistsinDb();

        $result = $this->retriever->getNotifiedUsers($this->project, $this->item_id);

        $expected_result = new \ArrayIterator(
            array(
                $this->user_id => array(
                    'item_id' => $this->item_id,
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION
                )
            )
        );
        $this->assertEqual(iterator_to_array($result), iterator_to_array($expected_result));
    }

    private function userDaoReturnsFalse($item_id)
    {
        stub($this->notified_users_dao)->searchUserIdByObjectIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->returnsDar(false);
    }

    private function userDaoReturnsFalseByCascade($item_id)
    {
        stub($this->notified_users_dao)->searchUserIdByObjectIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->returnsDar(false);
    }

    private function userDaoReturnsItemIdAndUser105($item_id)
    {
        stub($this->notified_users_dao)->searchUserIdByObjectIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->returnsDar(
            array(
                'item_id' => $item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION
            )
        );
    }

    private function userDaoReturnsItemIdAndUser105ByCascade($item_id)
    {
        stub($this->notified_users_dao)->searchUserIdByObjectIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->returnsDar(
            array(
                'item_id' => $item_id,
                'user_id' => $this->user_id,
                'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE
            )
        );
    }

    private function ugroupDaoReturnsFalse($item_id)
    {
        stub($this->notified_ugroups_dao)->searchUgroupsByItemIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->returnsDar(false);
    }

    private function ugroupDaoReturnsUgroup169($item_id)
    {
        stub($this->notified_ugroups_dao)->searchUgroupsByItemIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->returnsDar(
            array('ugroup_id' => 169)
        );
    }

    private function ugroupDaoReturnsUgroup169ByCascade($item_id)
    {
        stub($this->notified_ugroups_dao)->searchUgroupsByItemIdAndType(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->returnsDar(
            array('ugroup_id' => 169)
        );
    }

    private function itemExistsinDb()
    {
        $docman_item = mock('Docman_Item');
        stub($docman_item)->getParentId()->returns($this->parent_item_id);
        stub($this->item_factory)->getItemFromDb($this->item_id)->returns($docman_item);
    }

    private function parentItemExistsInDb()
    {
        $parent_docman_item = mock('Docman_Item');
        stub($this->item_factory)->getItemFromDb($this->parent_item_id)->returns($parent_docman_item);
    }
}
