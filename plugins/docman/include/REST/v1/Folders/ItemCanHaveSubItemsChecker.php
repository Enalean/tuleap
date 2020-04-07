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

namespace Tuleap\Docman\REST\v1\Folders;

use Tuleap\Docman\Item\ItemIsNotAFolderException;
use Tuleap\Docman\REST\v1\ItemCanHaveSubitemsCheckerVisitor;
use Tuleap\REST\I18NRestException;

class ItemCanHaveSubItemsChecker
{
    /**
     * @throws I18NRestException
     *
     * @psalm-assert \Docman_Folder $item
     */
    public function checkItemCanHaveSubitems(\Docman_Item $item)
    {
        $visitor = new ItemCanHaveSubitemsCheckerVisitor();
        try {
            $item->accept($visitor, []);
        } catch (ItemIsNotAFolderException $e) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The item %d is not a folder.'),
                    $item->getId()
                )
            );
        }
    }
}
