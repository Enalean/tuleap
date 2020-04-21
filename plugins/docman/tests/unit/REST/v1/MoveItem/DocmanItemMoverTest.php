<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\MoveItem;

use DateTimeImmutable;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

final class DocmanItemMoverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var Docman_PermissionsManager|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var DocmanItemMover
     */
    private $item_mover;
    /**
     * @var EventManager|Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->item_factory        = Mockery::mock(Docman_ItemFactory::class);
        $this->permissions_manager = Mockery::mock(Docman_PermissionsManager::class);
        $this->event_manager       = Mockery::mock(EventManager::class);

        $this->item_mover = new DocmanItemMover(
            $this->item_factory,
            new BeforeMoveVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
                $this->item_factory,
                Mockery::mock(DocumentOngoingUploadRetriever::class)
            ),
            $this->permissions_manager,
            $this->event_manager
        );
    }

    public function testAnItemCanBeMoved(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn($destination_folder_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->andReturn(true);

        $item_to_move = Mockery::mock(Docman_Item::class);
        $item_to_move->shouldReceive('getId')->andReturn(123);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn(146);

        $item_to_move->shouldReceive('accept')
            ->with(Mockery::type(BeforeMoveVisitor::class), Mockery::any());

        $this->item_factory->shouldReceive('moveWithDefaultOrdering')->once()->andReturn(true);
        $this->event_manager->shouldReceive('processEvent')->with('send_notifications')->atLeast()->once();

        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADestinationThatDoesNotExist(): void
    {
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = 147;

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            Mockery::mock(Docman_Item::class),
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADestinationTheUserCannotRead(): void
    {
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = 147;

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(Mockery::mock(Docman_Folder::class));

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            Mockery::mock(Docman_Item::class),
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADifferentProject(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);

        $item_to_move = Mockery::mock(Docman_Item::class);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(103);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoTheFolderTheItemIsAlreadyIn(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn($destination_folder_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);

        $item_to_move = Mockery::mock(Docman_Item::class);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn($destination_folder_id);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoSomethingThatIsNotAFolder(): void
    {
        $destination_id                        = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_id;

        $destination = Mockery::mock(Docman_Item::class);
        $destination->shouldReceive('getId')->andReturn($destination_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_id)->andReturn($destination);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->andReturn(true);

        $item_to_move = Mockery::mock(Docman_Item::class);
        $item_to_move->shouldReceive('getId')->andReturn(123);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn(146);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testCannotMoveAnItemTheUserCannotWrite(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $user = Mockery::mock(PFUser::class);

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn($destination_folder_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $item_to_move    = Mockery::mock(Docman_Item::class);
        $item_to_move_id = 123;
        $item_to_move->shouldReceive('getId')->andReturn($item_to_move_id);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn(146);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->with($user, $item_to_move_id)->andReturn(false);

        $item_to_move->shouldReceive('accept')
            ->with(Mockery::type(BeforeMoveVisitor::class), Mockery::any());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            $user,
            $representation
        );
    }

    public function testCannotMoveAnItemIntoAFolderTheUserCannotWrite(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $user = Mockery::mock(PFUser::class);

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn($destination_folder_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $item_to_move    = Mockery::mock(Docman_Item::class);
        $item_to_move_id = 123;
        $item_to_move->shouldReceive('getId')->andReturn($item_to_move_id);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn(146);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->with($user, $item_to_move_id)->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->with($user, $destination_folder_id)->andReturn(false);

        $item_to_move->shouldReceive('accept')
            ->with(Mockery::type(BeforeMoveVisitor::class), Mockery::any());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            $user,
            $representation
        );
    }

    public function testIssueWithTheMoveIsNotSilentlyIgnored(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn($destination_folder_id);
        $this->item_factory->shouldReceive('getItemFromDb')->with($destination_folder_id)->andReturn($destination_folder);

        $this->permissions_manager->shouldReceive('userCanAccess')->andReturn(true);
        $this->permissions_manager->shouldReceive('userCanWrite')->andReturn(true);

        $item_to_move = Mockery::mock(Docman_Item::class);
        $item_to_move->shouldReceive('getId')->andReturn(789);
        $item_to_move->shouldReceive('getGroupId')->andReturn(102);
        $destination_folder->shouldReceive('getGroupId')->andReturn(102);
        $item_to_move->shouldReceive('getParentId')->andReturn(146);

        $item_to_move->shouldReceive('accept')
            ->with(Mockery::type(BeforeMoveVisitor::class), Mockery::any());

        $this->item_factory->shouldReceive('moveWithDefaultOrdering')->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            Mockery::mock(PFUser::class),
            $representation
        );
    }
}
