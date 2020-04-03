<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\REST\I18NRestException;

/**
 * @template-implements ItemVisitor<void>
 */
class DocumentBeforeModificationValidatorVisitor implements ItemVisitor
{
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var Docman_Item
     */
    private $item;
    /**
     * @var \Docman_PermissionsManager
     */
    private $permission_manager;
    /**
     * @var DoesItemHasExpectedTypeVisitor
     */
    private $does_item_has_expected_type_visitor;

    public function __construct(
        \Docman_PermissionsManager $permission_manager,
        \PFUser $current_user,
        Docman_Item $item,
        DoesItemHasExpectedTypeVisitor $does_item_has_expected_type_visitor
    ) {
        $this->current_user                        = $current_user;
        $this->item                                = $item;
        $this->permission_manager                  = $permission_manager;
        $this->does_item_has_expected_type_visitor = $does_item_has_expected_type_visitor;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): void
    {
        $this->checkExpectedType($item);
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): void
    {
        $this->checkExpectedType($item);
        $this->checkUserCanWrite($this->current_user, $this->item);
    }

    public function visitLink(Docman_Link $item, array $params = []): void
    {
        $this->checkExpectedType($item);
        $this->checkUserCanWrite($this->current_user, $this->item);
    }

    public function visitFile(Docman_File $item, array $params = []): void
    {
        $this->checkExpectedType($item);
        $this->checkUserCanWrite($this->current_user, $this->item);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $this->checkExpectedType($item);
        $this->checkUserCanWrite($this->current_user, $this->item);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): void
    {
        $this->checkExpectedType($item);
        $this->checkUserCanWrite($this->current_user, $this->item);
    }

    public function visitItem(Docman_Item $item, array $params = []): void
    {
        $this->throwItemHasNotTheRightType();
    }

    /**
     * @throws I18NRestException
     */
    private function checkExpectedType(Docman_Item $item): void
    {
        if (! $item->accept($this->does_item_has_expected_type_visitor)) {
            $this->throwItemHasNotTheRightType();
        }
    }

    /**
     * @throws I18NRestException
     */
    private function throwItemHasNotTheRightType(): void
    {
        throw new I18NRestException(
            400,
            sprintf(
                'The provided item id references an item which is not a %s',
                $this->does_item_has_expected_type_visitor->getExpectedItemClass()
            )
        );
    }

    /**
     * @throws I18NRestException
     */
    private function checkUserCanWrite(\PFUser $user, \Docman_Item $item): void
    {
        if (! $this->permission_manager->userCanWrite($user, $item->getId())) {
            throw new I18NRestException(
                403,
                dgettext('tuleap-docman', 'You are not allowed to write this item.')
            );
        }
    }
}
