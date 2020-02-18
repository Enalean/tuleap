<?php
/*
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

require_once('Docman_ReportColumn.class.php');

require_once('Docman_MetadataFactory.class.php');

class Docman_ReportColumnFactory
{
    public $groupId;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    public function getColumnFromLabel($colLabel)
    {
        $col = null;
        $mdFactory = $this->_getMetadataFactory();
        switch ($colLabel) {
            case 'location':
                $col = new Docman_ReportColumnLocation();
                break;

            case 'title':
                $md  = $mdFactory->getFromLabel($colLabel);
                $col = new Docman_ReportColumnTitle($md);
                break;

            default:
                $md  = $mdFactory->getFromLabel($colLabel);
                switch ($md->getType()) {
                    case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                        $col = new Docman_ReportColumnList($md);
                        break;
                    default:
                        $col = new Docman_ReportColumn($md);
                }
        }
        return $col;
    }

    public function &_getMetadataFactory()
    {
        $mdf = new Docman_MetadataFactory($this->groupId);
        return $mdf;
    }
}
