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

use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

class ItemRepresentation
{
    const TYPE_FOLDER   = 'folder';
    const TYPE_FILE   = 'file';
    const TYPE_LINK   = 'link';
    const TYPE_EMBEDDED = 'embedded';
    const TYPE_WIKI   = 'wiki';
    const TYPE_EMPTY  = 'empty';

    /**
     * @var int {@type int}
     */
    public $item_id;

    /**
     * @var string {@type string}
     */
    public $name;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $owner;

    /**
     * @var string {@type string}
     */
    public $last_update_date;

    /**
     * @var string
     */
    public $type;

    public function __construct(\Docman_Item $item, MinimalUserRepresentation $owner, $type)
    {
        $this->item_id          = JsonCast::toInt($item->getId());
        $this->name             = $item->getTitle();
        $this->owner            = $owner;
        $this->last_update_date = JsonCast::toDate($item->getUpdateDate());
        $this->type             = $type;
    }
}
