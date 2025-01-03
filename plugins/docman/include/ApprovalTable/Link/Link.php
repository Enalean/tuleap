<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_ApprovalTableLink extends Docman_ApprovalTableVersionned
{
    private $item_id;
    private $versionId = null;

    public function setVersionId($v)
    {
        $this->versionId = $v;
    }

    public function getVersionId()
    {
        return $this->versionId;
    }

    public function getItemId()
    {
        return $this->item_id;
    }

    public function initFromRow($row)
    {
        parent::initFromRow($row);
        if (isset($row['link_version_id'])) {
            $this->versionId = $row['link_version_id'];
        }
        if (isset($row['version_number'])) {
            $this->versionNumber = $row['version_number'];
        }
        if (isset($row['link_item_id'])) {
            $this->item_id = $row['link_item_id'];
        }
    }
}
