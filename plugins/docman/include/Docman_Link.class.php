<?php
/**
 * Copyright (c) Enalean, 2017-present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * URL is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Link extends Docman_Document
{
    /**
     * @var Docman_LinkVersion
     */
    private $current_version;

    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    public $url;
    public function getUrl()
    {
        return $this->url;
    }
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getType()
    {
        return dgettext('tuleap-docman', 'Link');
    }

    public function initFromRow($row)
    {
        parent::initFromRow($row);
        $this->setUrl($row['link_url']);
    }
    public function toRow()
    {
        $row = parent::toRow();
        $row['link_url'] = $this->getUrl();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_LINK;
        return $row;
    }

    public function accept($visitor, $params = array())
    {
        return $visitor->visitLink($this, $params);
    }

    public function setCurrentVersion(Docman_LinkVersion $current_version)
    {
        $this->current_version = $current_version;
    }

    public function getCurrentVersion()
    {
        return $this->current_version;
    }
}
