<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_PermissionsManager;
use Tuleap\Docman\Item\ItemIsNotAFolderException;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Request\ForbiddenException;

class FolderAccessibilityCheckerVisitor implements ItemVisitor
{
    /**
     * @var Docman_PermissionsManager
     */
    private $docman_permission_manager;

    public function __construct(Docman_PermissionsManager $docman_permission_manager)
    {
        $this->docman_permission_manager = $docman_permission_manager;
    }

    public function visitFolder(Docman_Item $item, array $params = [])
    {
        if (! $this->docman_permission_manager->userCanAccess($params['user'], $item->getId())) {
            throw new ForbiddenException();
        }
    }

    public function visitWiki(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }

    public function visitLink(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }

    public function visitFile(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }

    public function visitEmbeddedFile(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }

    public function visitEmpty(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        throw new ItemIsNotAFolderException();
    }
}
