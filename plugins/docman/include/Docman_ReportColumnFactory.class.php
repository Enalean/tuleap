<?php
/**
 * Copyright © STMicroelectronics, 2007. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2007.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * $Id$
 */

require_once('Docman_ReportColumn.class.php');

require_once('Docman_MetadataFactory.class.php');

class Docman_ReportColumnFactory {
    var $groupId;

    function Docman_ReportColumnFactory($groupId) {
        $this->groupId = $groupId;
    }

    function getColumnFromLabel($colLabel) {
        $col = null;
        $mdFactory = $this->_getMetadataFactory();
        switch($colLabel) {
        case 'location':
            $col = new Docman_ReportColumnLocation();
            break;

        case 'title':
            $md  = $mdFactory->getFromLabel($colLabel);
            $col = new Docman_ReportColumnTitle($md);
            break;

        default:
            $md  = $mdFactory->getFromLabel($colLabel);
            switch($md->getType()) {
            case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                $col = new Docman_ReportColumnList($md);
                break;
            default:
                $col = new Docman_ReportColumn($md);
            }
        }
        return $col;
    }

    function &_getMetadataFactory() {
        $mdf = new Docman_MetadataFactory($this->groupId);
        return $mdf;
    }
}

?>
