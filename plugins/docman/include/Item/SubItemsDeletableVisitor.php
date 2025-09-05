<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item;

/**
 * Check if all the sub items are deletable by given user.
 * @template-implements ItemVisitor<bool>
 */
class SubItemsDeletableVisitor implements ItemVisitor
{
    public function __construct(private \Docman_PermissionsManager $permissions_manager, private \PFUser $user)
    {
    }

    #[\Override]
    public function visitFolder(\Docman_Folder $item, array $params = []): bool
    {
        if (! $this->itemIsDeletable($item)) {
            return false;
        }

        $sub_items = $item->getAllItems();
        if ($sub_items && $sub_items->size() > 0) {
            foreach ($sub_items->iterator() as $child) {
                $is_child_deletable = $child->accept($this, $params);
                if (! $is_child_deletable) {
                    return false;
                }
            }
        }

        return true;
    }

    public function visitDocument(\Docman_Document $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitOtherDocument(OtherDocument $item, array $params = [])
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitWiki(\Docman_Wiki $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitLink(\Docman_Link $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitFile(\Docman_File $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitEmbeddedFile(\Docman_EmbeddedFile $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitEmpty(\Docman_Empty $item, array $params = []): bool
    {
        return $this->visitItem($item, $params);
    }

    #[\Override]
    public function visitItem(\Docman_Item $item, array $params = []): bool
    {
        return $this->itemIsDeletable($item);
    }

    private function itemIsDeletable(\Docman_Item $item): bool
    {
        return $this->permissions_manager->userCanDelete($this->user, $item);
    }
}
