<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use Docman_ItemDao;
use Docman_ItemFactory;
use Mockery;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class ItemRepresentationBuilderTest extends \PHPUnit\Framework\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Docman_ItemDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    /**
     * @var ItemRepresentationBuilder
     */
    private $item_representation_builder;

    protected function setUp() : void
    {
        parent::setUp();

        $this->dao                  = Mockery::Mock(Docman_ItemDao::class);
        $this->user_manager         = Mockery::Mock(\UserManager::class);
        \UserManager::setInstance($this->user_manager);
        \CodendiDataAccess::setInstance(\Mockery::spy(LegacyDataAccessInterface::class));
        $this->docman_item_factory  = Mockery::Mock(Docman_ItemFactory::class);
        $this->permissions_manager  = Mockery::Mock(\Docman_PermissionsManager::class);
        $this->lock_factory         = Mockery::Mock(\Docman_LockFactory::class);

        $this->item_representation_builder = new ItemRepresentationBuilder(
            $this->dao,
            $this->user_manager,
            $this->docman_item_factory,
            $this->permissions_manager,
            $this->lock_factory
        );
    }

    protected function tearDown() : void
    {
        \UserManager::clearInstance();
        \CodendiDataAccess::clearInstance();
    }

    public function testItBuildsAnItemRepresentationOfAnItem() : void
    {
        $owner_id       = 123;
        $docman_item_id = 666;
        $current_user   = Mockery::mock(\PFUser::class);
        $current_user->shouldReceive('getId')->andReturns($owner_id);
        $current_user->shouldReceive('getName')->andReturns('toto');
        $current_user->shouldReceive('getUserName')->andReturns('toto');
        $current_user->shouldReceive('getRealName')->andReturns('toto');
        $current_user->shouldReceive('isAnonymous')->andReturns(false);
        $current_user->shouldReceive('isNone')->andReturns(false);
        $current_user->shouldReceive('getLdapId')->andReturns('');
        $current_user->shouldReceive('getAvatarUrl')->andReturns('some/avatar/url');
        $current_user->shouldReceive('hasAvatar')->andReturns(false);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($current_user);
        $current_user->shouldReceive('getPreference')->with('username_display')->andReturns('toto');

        $docman_item    = new \Docman_Item();
        $docman_item->setId($docman_item_id);
        $docman_item->setTitle('My file.txt');
        $docman_item->setOwnerId($owner_id);

        $this->user_manager->shouldReceive('getUserById')
            ->withArgs([$owner_id])
            ->andReturns($current_user);

        $this->permissions_manager->shouldReceive('userCanWrite')
            ->withArgs([$current_user, $docman_item_id])
            ->andReturns(true);

        $this->lock_factory->shouldReceive('getLockInfoForItem')
            ->withArgs([$docman_item])
            ->andReturns(["user_id" => $owner_id, "lock_date" => 1549461600]);

        $representation = $this->item_representation_builder->buildItemRepresentation(
            $docman_item,
            $current_user,
            ItemRepresentation::TYPE_FILE
        );

        $this->assertEquals($representation->title, 'My file.txt');
        $this->assertEquals($representation->is_expanded, false);
        $this->assertEquals($representation->user_can_write, true);
        $this->assertEquals($representation->lock_info->locked_by->id, $owner_id);
        $this->assertEquals($representation->lock_info->lock_date, '2019-02-06T15:00:00+01:00');
        $this->assertEquals($representation->file_properties, null);
        $this->assertEquals($representation->embedded_file_properties, null);
        $this->assertEquals($representation->link_properties, null);
        $this->assertEquals($representation->wiki_properties, null);
    }
}
