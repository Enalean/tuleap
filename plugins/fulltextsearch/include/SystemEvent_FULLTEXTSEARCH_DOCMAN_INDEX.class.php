<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


class SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX extends SystemEvent_FULLTEXTSEARCH_DOCMAN {

    protected function processItem(Docman_Item $item) {
        $version_number = (int)$this->getRequiredParameter(2);
        $version = $this->getVersion($item, $version_number);
        if ($version) {
            $this->actions->indexNewDocument($item, $version);
            return true;
        }
        $this->error('Version not found');
    }
}
?>
