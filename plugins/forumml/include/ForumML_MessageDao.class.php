<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

class ForumML_MessageDao extends DataAccessObject
{

    public function __construct($da)
    {
        parent::__construct($da);
    }

    public function searchHeaderValue($messageId, $headerId)
    {
        $sql = 'SELECT mh.value' .
            ' FROM plugin_forumml_message m' .
            '  JOIN plugin_forumml_messageheader mh' .
            '   ON (mh.id_message = m.id_message)' .
            '  JOIN plugin_forumml_header h' .
            '   ON (h.id_header = mh.id_header)' .
            ' WHERE m.id_message = ' . $this->da->quoteSmart($messageId) .
            '  AND h.id_header = ' . $this->da->quoteSmart($headerId);
        return $this->retrieve($sql);
    }
}
