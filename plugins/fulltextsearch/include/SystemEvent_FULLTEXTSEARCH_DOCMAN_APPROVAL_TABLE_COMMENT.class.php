<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT extends SystemEvent_FULLTEXTSEARCH_DOCMAN {
    const NAME = 'FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT';

    /**
     * Process the system event
     *
     * @return bool
     */
    public function processItem(Docman_Item $item) {
        try {
            $this->actions->indexDocumentApprovalComment($item);
            return true;
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        return false;
    }
}
