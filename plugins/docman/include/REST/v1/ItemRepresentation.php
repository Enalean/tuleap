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
    public $id;

    /**
     * @var string {@type string}
     */
    public $title;

    /**
     * @var MinimalUserRepresentation {@type MinimalUserRepresentation}
     */
    public $owner;

    /**
     * @var string {@type string}
     */
    public $last_update_date;

    /**
     * @var bool {@type bool}
     */
    public $user_can_write;

    /**
     * @var string
     */
    public $type;

    /**
     * @var FilePropertiesRepresentation | null
     */
    public $file_properties;

    /**
     * @var LinkPropertiesRepresentation | null
     */
    public $link_properties;

    public function build(
        \Docman_Item $item,
        MinimalUserRepresentation $owner,
        $user_can_write,
        $type,
        FilePropertiesRepresentation $file_properties = null,
        LinkPropertiesRepresentation $link_properties = null
    ) {
        $this->id               = JsonCast::toInt($item->getId());
        $this->title            = $item->getTitle();
        $this->owner            = $owner;
        $this->last_update_date = JsonCast::toDate($item->getUpdateDate());
        $this->user_can_write   = $user_can_write;
        $this->type             = $type;
        $this->file_properties  = $file_properties;
        $this->link_properties  = $link_properties;
    }
}
