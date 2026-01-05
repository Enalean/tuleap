<?php
/**
 * Copyright (c) Enalean, 2017-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

/**
 * Folder is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Folder extends Docman_Item //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct($data = null)
    {
        parent::__construct($data);
        $this->resetItems();
    }

    #[\Override]
    public function getType(): string
    {
        return dgettext('tuleap-docman', 'Folder');
    }

    #[\Override]
    public function toRow(): array
    {
        $row              = parent::toRow();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
        return $row;
    }

    public function isRoot(): bool
    {
        return $this->parent_id == 0;
    }

    public array $items = [];
    public function addItem(&$item): void
    {
        $this->items[] = $item;
    }

    public function &getAllItems(): array
    {
        return $this->items;
    }

    public function removeAllItems(): void
    {
        $this->resetItems();
    }

    private function resetItems(): void
    {
        $this->items = [];
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    #[\Override]
    public function accept($visitor, $params = [])
    {
        return $visitor->visitFolder($this, $params);
    }
}
