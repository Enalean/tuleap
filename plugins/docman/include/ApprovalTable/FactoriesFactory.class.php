<?php
/**
 * Copyright (c) Enalean, 2014 - 2019. All Rights Reserved.
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

class Docman_ApprovalTableFactoriesFactory
{
    /**
     * Return the right ApprovalTableFactory depending of the item.
     */
    public static function getFromItem($item, $version = null)
    {
        $appTableFactory = null;
        if ($item instanceof Docman_File) {
            $appTableFactory = new Docman_ApprovalTableFileFactory($item, $version);
        } elseif ($item instanceof Docman_Wiki) {
            $appTableFactory = new Docman_ApprovalTableWikiFactory($item, $version);
        } elseif ($item instanceof Docman_Link) {
            $appTableFactory = new Docman_ApprovalTableLinkFactory($item, $version);
        } elseif ($item instanceof Docman_Empty) {
            // there is no approval table for empty documents.
        } else {
            $appTableFactory = new Docman_ApprovalTableItemFactory($item);
        }
        return $appTableFactory;
    }

    public function getSpecificFactoryFromItem($item, $version = null)
    {
        return self::getFromItem($item, $version);
    }

    public function getReviewerFactory(Docman_ApprovalTable $table, Docman_Item $item)
    {
        return new Docman_ApprovalTableReviewerFactory($table, $item);
    }
}
