<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Statistics_DiskUsageManager.class.php';

class Statistics_DiskUsageOutput {
    protected $_dum;
    
    public function __construct(Statistics_DiskUsageManager $dum) {
        $this->_dum = $dum;
    }

    /**
     * Return human readable sizes
     *
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.3.0
     * @link        http://aidanlister.com/repos/v/function.size_readable.php
     * @param       int     $size        size in bytes
     * @param       string  $max         maximum unit
     * @param       string  $system      'si' for SI, 'bi' for binary prefixes
     * @param       string  $retstring   return string format
     */
    public function sizeReadable($size, $max = null, $system = 'bi', $retstring = '%d %s')
    {
        // Pick units
        $systems['si']['prefix'] = array('B', 'K', 'MB', 'GB', 'TB', 'PB');
        $systems['si']['size']   = 1000;
        $systems['bi']['prefix'] = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        $systems['bi']['size']   = 1024;
        $sys = isset($systems[$system]) ? $systems[$system] : $systems['si'];

        // Max unit to display
        $depth = count($sys['prefix']) - 1;
        if ($max && false !== $d = array_search($max, $sys['prefix'])) {
            $depth = $d;
        }

        // Loop
        $i = 0;
        while (abs($size) >= $sys['size'] && $i < $depth) {
            $size /= $sys['size'];
            $i++;
        }

        return sprintf($retstring, $size, $sys['prefix'][$i]);
    }

    /**
     * Return a human readable string for service
     * 
     * @param String $service
     * 
     * @return String
     */
    public function getServiceTitle($service) {
        switch($service) {
            case Statistics_DiskUsageManager::SVN:
                return 'SVN';
            case Statistics_DiskUsageManager::CVS:
                return 'CVS';
            case Statistics_DiskUsageManager::FRS:
                return 'File releases';
            case Statistics_DiskUsageManager::FTP:
                return 'Public FTP';
            case Statistics_DiskUsageManager::WIKI:
                return 'Wiki';
            case Statistics_DiskUsageManager::MAILMAN:
                return 'Mailman';
            case Statistics_DiskUsageManager::PLUGIN_DOCMAN:
                return 'Docman';
            case Statistics_DiskUsageManager::PLUGIN_FORUMML:
                return 'ForumML';
            case Statistics_DiskUsageManager::PLUGIN_WEBDAV:
                return 'Webdav';
            case Statistics_DiskUsageManager::GRP_HOME:
                return 'Groups';
            case Statistics_DiskUsageManager::USR_HOME:
                return 'Users';
            case Statistics_DiskUsageManager::MYSQL:
                return 'MySQL';
            case Statistics_DiskUsageManager::CODENDI_LOGS:
                return 'Codendi Logs';
            case Statistics_DiskUsageManager::BACKUP:
                return 'Backup';
            case Statistics_DiskUsageManager::BACKUP_OLD:
                return 'BackupOld';
            default:
                return false;
        }
    }


}

?>
