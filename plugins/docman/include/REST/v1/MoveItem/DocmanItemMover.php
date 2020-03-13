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
use RuntimeException;
use Tuleap\REST\I18NRestException;

final class DocmanItemMover
{
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var BeforeMoveVisitor
     */
    private $before_move_visitor;
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        Docman_ItemFactory $item_factory,
        BeforeMoveVisitor $before_move_visitor,
        Docman_PermissionsManager $permissions_manager,
        EventManager $event_manager
    ) {
        $this->item_factory        = $item_factory;
        $this->before_move_visitor = $before_move_visitor;
        $this->permissions_manager = $permissions_manager;
        $this->event_manager       = $event_manager;
    }

    public function moveItem(
        DateTimeImmutable $current_time,
        Docman_Item $item_to_move,
        PFUser $user,
        DocmanMoveItemRepresentation $representation
    ) : void {
        $destination_item_id = $representation->destination_folder_id;
        $destination_folder  = $this->item_factory->getItemFromDb($representation->destination_folder_id);
        if ($destination_folder === null || ! $this->permissions_manager->userCanAccess($user, $destination_item_id)) {
            throw new RestException(
                404,
                sprintf('Cannot move an item into the item #%d, the destination does not exist', $destination_item_id)
            );
        }

        if ($destination_folder->getGroupId() !== $item_to_move->getGroupId()) {
            throw new RestException(
                400,
                'The destination and the moved item are not in the same project'
            );
        }

        if ($item_to_move->getParentId() === $destination_folder->getId()) {
            throw new RestException(
                400,
                'The moved item is already in the destination folder'
            );
        }

        $item_to_move_id = $item_to_move->getId();
        if (! $this->permissions_manager->userCanWrite($user, $item_to_move_id)) {
            throw new RestException(
                403,
                sprintf('You does not have the right to move item #%d', $item_to_move_id)
            );
        }

        if (! $destination_folder instanceof Docman_Folder) {
            throw new RestException(
                400,
                sprintf('Cannot move an item into the item #%d, the destination is not a folder', $destination_item_id)
            );
        }

        if (! $this->permissions_manager->userCanWrite($user, $destination_item_id)) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-docman', "You are not allowed to write on folder with id '%d'"),
                    $destination_item_id
                )
            );
        }

        $item_to_move->accept(
            $this->before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => $current_time]
        );

        $has_item_been_moved = $this->item_factory->moveWithDefaultOrdering($item_to_move, $destination_folder, $user);
        if (! $has_item_been_moved) {
            throw new RuntimeException(
                sprintf(
                    'Something unexpected has happened during the move of #%d into #%d',
                    $item_to_move_id,
                    $destination_item_id
                )
            );
        }

        $this->event_manager->processEvent('send_notifications');
    }
}
