<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
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
require_once('Docman_Document.class.php');

/**
 * URL is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_File extends Docman_Document
{

    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    /**
     * @var Docman_Version
     */
    public $currentVersion;
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    public function getType()
    {
        $version      = $this->getCurrentVersion();
        $default_type = dgettext('tuleap-docman', 'File');
        $type         = $version ? $version->getFiletype() : $default_type;
        return $type;
    }

    public function toRow()
    {
        $row = parent::toRow();
        $row['item_type'] = PLUGIN_DOCMAN_ITEM_TYPE_FILE;
        return $row;
    }

    public function accept($visitor, $params = array())
    {
        return $visitor->visitFile($this, $params);
    }
}
