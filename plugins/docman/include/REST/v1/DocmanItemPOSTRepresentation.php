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

class DocmanItemPOSTRepresentation
{
    /**
     * @var string Item title {@from body} {@required true}
     */
    public $title;
    /**
     * @var string Item description {@from body} {@required false}
     */
    public $description = '';
    /**
     * @var int Item parent id {@from body} {@required true}
     */
    public $parent_id;
    /**
     * @var string Item type {@choice empty,wiki,file} {@from body} {@required true}
     */
    public $type;
    /**
     * @var FilePropertiesPOSTRepresentation File properties must be set when creating a new file {@from body} {@required false} {@type \Tuleap\Docman\REST\v1\FilePropertiesPOSTRepresentation}
     */
    public $file_properties = null;
    /**
     * @var WikiPropertiesPOSTRepresentation {@type \Tuleap\Docman\REST\v1\WikiPropertiesPOSTRepresentation} {@from body} {@required false}
     */
    public $wiki_properties = null;
}
