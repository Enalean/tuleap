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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use UGroupManager;

class NotifiedPeopleRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectUGroup
     */
    private $custom_ugroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = \Mockery::spy(
            \Project::class,
            ['getID' => false, 'getUnixName' => false, 'isPublic' => false]
        );

        $this->notified_users_dao   = \Mockery::spy(\Tuleap\Docman\Notifications\UsersToNotifyDao::class);
        $this->notified_ugroups_dao = \Mockery::spy(\Tuleap\Docman\Notifications\UgroupsToNotifyDao::class);
        $this->ugroup_manager       = \Mockery::spy(\UgroupManager::class);
        $this->item_factory         = \Mockery::spy(\Docman_ItemFactory::class);

        $this->retriever = new NotifiedPeopleRetriever(
            $this->notified_users_dao,
            $this->notified_ugroups_dao,
            $this->item_factory,
            $this->ugroup_manager
        );

        $this->user_id        = 105;
        $this->item_id        = 66;
        $this->parent_item_id = 65;

        $this->custom_ugroup = \Mockery::mock(\ProjectUGroup::class);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, 169)->andReturns($this->custom_ugroup);
    }

    public function testItNotifiesUsersListeningToItem(): void
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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUsersListeningToParentOfItem(): void
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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItDoesNotNotifyTwiceTheSameUser(): void
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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUgroupMembersListeningToItem(): void
    {
        $this->userDaoReturnsFalse($this->item_id);
        $this->ugroupDaoReturnsUgroup169($this->item_id);

        $this->itemExistsinDb();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn($this->user_id);
        $this->custom_ugroup->shouldReceive('getMembers')->andReturn([$user]);

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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItNotifiesUgroupMembersListeningToParentOfItem(): void
    {
        $this->userDaoReturnsFalse($this->item_id);
        $this->ugroupDaoReturnsFalse($this->item_id);
        $this->userDaoReturnsFalseByCascade($this->parent_item_id);
        $this->ugroupDaoReturnsUgroup169ByCascade($this->parent_item_id);

        $this->itemExistsinDb();
        $this->parentItemExistsInDb();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn($this->user_id);
        $this->custom_ugroup->shouldReceive('getMembers')->andReturn([$user]);

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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    public function testItDoesNotNotifyTwiceTheSameUserInUgroupAndInList(): void
    {
        $this->userDaoReturnsItemIdAndUser105($this->item_id);
        $this->ugroupDaoReturnsUgroup169($this->item_id);

        $this->itemExistsinDb();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn($this->user_id);
        $this->custom_ugroup->shouldReceive('getMembers')->andReturn([$user]);

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
        $this->assertEquals(iterator_to_array($expected_result), iterator_to_array($result));
    }

    private function userDaoReturnsFalse($item_id): void
    {
        $this->notified_users_dao->shouldReceive('searchUserIdByObjectIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->andReturns(\TestHelper::arrayToDar(false));
    }

    private function userDaoReturnsFalseByCascade($item_id): void
    {
        $this->notified_users_dao->shouldReceive('searchUserIdByObjectIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->andReturns(\TestHelper::arrayToDar(false));
    }

    private function userDaoReturnsItemIdAndUser105($item_id): void
    {
        $this->notified_users_dao->shouldReceive('searchUserIdByObjectIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->andReturns(
            \TestHelper::arrayToDar(
                array(
                    'item_id' => $item_id,
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION
                )
            )
        );
    }

    private function userDaoReturnsItemIdAndUser105ByCascade($item_id): void
    {
        $this->notified_users_dao->shouldReceive('searchUserIdByObjectIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->andReturns(
            \TestHelper::arrayToDar(
                array(
                    'item_id' => $item_id,
                    'user_id' => $this->user_id,
                    'type'    => PLUGIN_DOCMAN_NOTIFICATION_CASCADE
                )
            )
        );
    }

    private function ugroupDaoReturnsFalse($item_id): void
    {
        $this->notified_ugroups_dao->shouldReceive('searchUgroupsByItemIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->andReturns(\TestHelper::arrayToDar(false));
    }

    private function ugroupDaoReturnsUgroup169($item_id): void
    {
        $this->notified_ugroups_dao->shouldReceive('searchUgroupsByItemIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION
        )->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 169)));
    }

    private function ugroupDaoReturnsUgroup169ByCascade($item_id): void
    {
        $this->notified_ugroups_dao->shouldReceive('searchUgroupsByItemIdAndType')->with(
            $item_id,
            PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        )->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 169)));
    }

    private function itemExistsinDb(): void
    {
        $docman_item = \Mockery::spy(\Docman_Item::class);
        $docman_item->shouldReceive('getParentId')->andReturns($this->parent_item_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($this->item_id)->andReturns($docman_item);
    }

    private function parentItemExistsInDb(): void
    {
        $parent_docman_item = \Mockery::spy(\Docman_Item::class);
        $this->item_factory->shouldReceive('getItemFromDb')->with($this->parent_item_id)->andReturns(
            $parent_docman_item
        );
    }
}
