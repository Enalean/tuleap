<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Docman_Document.class.php');

class Docman_Empty extends Docman_Document
{

    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    public function accept($visitor, $params = array())
    {
        return $visitor->visitEmpty($this, $params);
    }

    public function toRow()
    {
        $row = parent::toRow();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_EMPTY;
        return $row;
    }

    public function getType()
    {
        return dgettext('tuleap-docman', 'Empty');
    }
}
