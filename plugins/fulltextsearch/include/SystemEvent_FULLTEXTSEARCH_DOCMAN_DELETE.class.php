<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

class SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE extends SystemEvent_FULLTEXTSEARCH_DOCMAN {
    const NAME = 'FULLTEXTSEARCH_DOCMAN_DELETE';

    protected function processItem(Docman_Item $item) {
        $this->actions->delete($item);
        return true;
    }

    protected function getItem($item_id,  $params = [])
    {
        return parent::getItem($item_id, array('ignore_deleted' => true));
    }
}
?>
