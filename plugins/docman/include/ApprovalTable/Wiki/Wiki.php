<?php
/**
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

class Docman_ApprovalTableWiki extends Docman_ApprovalTableVersionned
{
    public mixed $itemId        = null;
    public mixed $wikiVersionId = null;

    public function setItemId($v)
    {
        $this->itemId = $v;
    }

    public function getItemId()
    {
        return $this->itemId;
    }

    public function setWikiVersionId($v)
    {
        $this->wikiVersionId = $v;
        $this->versionNumber = $v;
    }

    public function getWikiVersionId()
    {
        return $this->wikiVersionId;
    }

    public function initFromRow($row)
    {
        parent::initFromRow($row);
        if (isset($row['item_id'])) {
            $this->itemId = $row['item_id'];
        }
        if (isset($row['wiki_version_id'])) {
            $this->wikiVersionId = $row['wiki_version_id'];
            $this->versionNumber = $row['wiki_version_id'];
        }
    }
}
