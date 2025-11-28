<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Docman\Item\OtherDocument;

class Docman_ApprovalTableFactoriesFactory implements \Tuleap\Docman\ApprovalTable\TableFactoryForFileBuilder // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    #[\Override]
    public function getTableFactoryForFile(Docman_File $item): Docman_ApprovalTableFileFactory
    {
        return new Docman_ApprovalTableFileFactory($item, null);
    }

    /**
     * Return the right ApprovalTableFactory depending of the item.
     */
    public function getFromItem($item, $version = null)
    {
        $appTableFactory = null;
        if ($item instanceof Docman_File) {
            $appTableFactory = new Docman_ApprovalTableFileFactory($item, $version);
        } elseif ($item instanceof Docman_Wiki) {
            $appTableFactory = new Docman_ApprovalTableWikiFactory($item, $version);
        } elseif ($item instanceof Docman_Link) {
            $appTableFactory = new Docman_ApprovalTableLinkFactory($item, $version);
        } elseif ($item instanceof Docman_Empty || $item instanceof OtherDocument) {
            // there is no approval table for empty and other documents.
        } else {
            $appTableFactory = new Docman_ApprovalTableItemFactory($item);
        }
        return $appTableFactory;
    }

    public function getSpecificFactoryFromItem($item, $version = null)
    {
        return $this->getFromItem($item, $version);
    }

    public function getReviewerFactory(Docman_ApprovalTable $table, Docman_Item $item)
    {
        return new Docman_ApprovalTableReviewerFactory($table, $item);
    }
}
