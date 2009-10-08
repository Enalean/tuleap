<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2007
 *
 * This file is a part of codendi.
 *
 * codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

/**
 * A class to handle mails attachments storage
 * 
 */
class ForumML_FileStorage {
    
    // Root directory to host mails attachments 
	var $root;
    
    /**
     * ForumML_FileStorage Constructor
     * 
     * @param root: The ForumML attachments directory 
     */
    function __construct($root) {
        $this->root = $root;
    }
    
    /**
     * Store - stores attached files in the ForumML root dir
     * 
     * @param filename: name of attached file
     * @param content: content of attached file
     * @param list: mailing-list name
     * @param date: date of attachment in YYYY_MM_DD format
     * @param encod: encoding of attachment
     * 
     * @return int size of attached file
     */
    function store($filename, $content, $list, $date, $encod="") {
        $path = $this->_getPath($filename, $list, $date, "store");
        $ret = file_put_contents($path, $content);
        if ($ret !== false) {
            return $path;
        } else {
            return false;
        }
    }
    
    /**
    * Store:
    *  +---------------------------------------------------------------------------------+
    *  |                             +-----------------------------------------+         |
    *  |                             |                                         |         |
    * _|__              _______     _|__                                       |         |
    * name              list_id     date                                       v         v
    * Attach.doc           7      2007_10_19              =>  foruuml_dir/<listname>/2007_10_19/Attach_doc 
    *                      |                                              ^ 
    *                      +---------------------------------------------+|
    *                     
    *
    * Upload (to temporary location):
    *  +-----------------------------------------------------------------------+
    *  |                                                                       |
    *  |                                                                       |
    * _|__                                                                     |
    * name                                                                     v
    * Attach.doc                                     =>  foruuml_dir/upload/Attach_doc     
    * 
    */

    /**
     * _getPath - Get the absolute path where to Upload/Store attached file
     * 
     * @param name: basename of attached file
     * @param list: mailing-list name
     * @param date: attachment date (YYYY_MM_DD)
     * @param string type: upload/store 
     * 
     * @return string path
     */
    function _getPath($name, $list, $date, $type) {
        $name = trim($name);

        if (trim($name) == '') {
            $name = 'attachment';
        } else {
            $name = mb_convert_encoding($name, 'ascii', 'utf-8');
            // restrict file name to 64 characters (maximum)
            if (strlen($name) > 64) {
                $name = substr($name, 0, 64);
            }
    	
            $name = preg_replace('`[^a-z0-9_-]`i', '_', $name);
            $name = preg_replace('`_{2,}`', '_', $name);
        }

        if ($type == "upload") {
        	$path_elements = array($this->root, $type);
        } else if ($type == "store") {
        	$path_elements = array($this->root, $list, $date);	
        }
        
        $path = '';
        foreach($path_elements as $elem) {
            $path .= $elem .'/';
            if (!is_dir($path)) {
                mkdir($path, 0755);
            }
        }

        // Ensure that same file doesn't exists yet
        $ext = '';
        $i   = 1;
        while($this->fileExists($path.$name.$ext)) {
            $ext = '_'.$i;
            $i++;
        }

        return $path.$name.$ext;
    }

    function fileExists($path) {
        return is_file($path);
    }
    
}

?>
