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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemMoverTest extends TestCase
{
    private Docman_ItemFactory&MockObject $item_factory;
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private DocmanItemMover $item_mover;
    private EventManager&MockObject $event_manager;

    protected function setUp(): void
    {
        $this->item_factory        = $this->createMock(Docman_ItemFactory::class);
        $this->permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->event_manager       = $this->createMock(EventManager::class);

        $this->item_mover = new DocmanItemMover(
            $this->item_factory,
            new BeforeMoveVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
                $this->item_factory,
                $this->createMock(DocumentOngoingUploadRetriever::class)
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

        $destination_folder = new Docman_Folder(['item_id' => $destination_folder_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $item_to_move = $this->createMock(Docman_Item::class);
        $item_to_move->method('getId')->willReturn(123);
        $item_to_move->method('getGroupId')->willReturn(102);
        $item_to_move->method('getParentId')->willReturn(146);
        $item_to_move->method('accept')->with(self::isInstanceOf(BeforeMoveVisitor::class), self::anything());

        $this->item_factory->expects(self::once())->method('moveWithDefaultOrdering')->willReturn(true);
        $this->event_manager->expects(self::atLeastOnce())->method('processEvent')->with('send_notifications');

        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADestinationThatDoesNotExist(): void
    {
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = 147;

        $this->item_factory->method('getItemFromDb')->willReturn(null);

        self::expectException(RestException::class);
        self::expectExceptionCode(404);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            new Docman_Item(),
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADestinationTheUserCannotRead(): void
    {
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = 147;

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());

        $this->permissions_manager->method('userCanAccess')->willReturn(false);

        self::expectException(RestException::class);
        self::expectExceptionCode(404);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            new Docman_Item(),
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoADifferentProject(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = new Docman_Folder(['group_id' => 103]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);

        $item_to_move = new Docman_Item(['group_id' => 102]);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoTheFolderTheItemIsAlreadyIn(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $destination_folder = new Docman_Folder(['item_id' => $destination_folder_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);

        $item_to_move = new Docman_Item(['group_id' => 102, 'parent_id' => $destination_folder_id]);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemIntoSomethingThatIsNotAFolder(): void
    {
        $destination_id                        = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_id;

        $destination = new Docman_Item(['item_id' => $destination_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_id)->willReturn($destination);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $item_to_move = new Docman_Item(['item_id' => 123, 'group_id' => 102, 'parent_id' => 146]);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testCannotMoveAnItemTheUserCannotWrite(): void
    {
        $destination_folder_id                 = 147;
        $representation                        = new DocmanMoveItemRepresentation();
        $representation->destination_folder_id = $destination_folder_id;

        $user = UserTestBuilder::buildWithDefaults();

        $destination_folder = new Docman_Folder(['item_id' => $destination_folder_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $item_to_move    = $this->createMock(Docman_Item::class);
        $item_to_move_id = 123;
        $item_to_move->method('getId')->willReturn($item_to_move_id);
        $item_to_move->method('getGroupId')->willReturn(102);
        $item_to_move->method('getParentId')->willReturn(146);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->with($user, $item_to_move_id)->willReturn(false);

        $item_to_move->method('accept')->with(self::isInstanceOf(BeforeMoveVisitor::class), self::anything());

        self::expectException(RestException::class);
        self::expectExceptionCode(403);
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

        $user = UserTestBuilder::buildWithDefaults();

        $destination_folder = new Docman_Folder(['item_id' => $destination_folder_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $item_to_move    = $this->createMock(Docman_Item::class);
        $item_to_move_id = 123;
        $item_to_move->method('getId')->willReturn($item_to_move_id);
        $item_to_move->method('getGroupId')->willReturn(102);
        $item_to_move->method('getParentId')->willReturn(146);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->willReturnCallback(static fn(PFUser $user, int $item_id) => $item_id === $item_to_move_id);

        $item_to_move->method('accept')->with(self::isInstanceOf(BeforeMoveVisitor::class), self::anything());

        self::expectException(RestException::class);
        self::expectExceptionCode(403);
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

        $destination_folder = new Docman_Folder(['item_id' => $destination_folder_id, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->with($destination_folder_id)->willReturn($destination_folder);

        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $item_to_move = $this->createMock(Docman_Item::class);
        $item_to_move->method('getId')->willReturn(789);
        $item_to_move->method('getGroupId')->willReturn(102);
        $item_to_move->method('getParentId')->willReturn(146);
        $item_to_move->method('accept')->with(self::isInstanceOf(BeforeMoveVisitor::class), self::anything());

        $this->item_factory->method('moveWithDefaultOrdering')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->item_mover->moveItem(
            new DateTimeImmutable(),
            $item_to_move,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }
}
