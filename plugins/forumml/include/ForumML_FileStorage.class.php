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
    function ForumML_FileStorage($root) {
    	
        $this->root = $root;
    }
    
    /**
     * Upload - uploads attached files to temporary location
     * 
     * @param att_array: $_FILES array
     * 
     * @return files_array: array of attached files attributes (name, path, type)
     */
    function upload($att_array) {
    	
    	$idx = 0;
    	$files_array = array(   "name" => array(),
    							"path" => array(),
    							"type" => array());
    	foreach ($att_array["name"] as $att_key => $att_name) {		
    		if ($att_name != "") {
				$files_array["name"][$idx] = $att_name;
				$files_array["type"][$idx] = $att_array["type"][$att_key];
    			$tmp_name = $att_array["tmp_name"][$att_key];		
				$path = $this->_getPath($att_name, "", "", "upload");
        		if (is_uploaded_file($tmp_name)) {
        			move_uploaded_file($tmp_name, $path);            	
        		}
        		$files_array["path"][$idx] = $path;
				$idx++;
        	}
    	}
        return $files_array;
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
    function store($filename, $content, $list, $date, $encod) {
    	
        $path = $this->_getPath($filename, $list, $date, "store");
        $tmp = fopen($path, 'w');
        switch ($encod)
	  		{
	  		case 'base64':
	    		fwrite($tmp,base64_decode($content));
	    		break;
	  		default :
	    		fwrite($tmp,$content);
	    		break;
	  		}
		fclose($tmp);
		return filesize($path);

    }
    
    /**
     * Delete - deletes attached file from temporary location
     * 
     * @param path: full path of attached file 
     * 
     * @return boolean
     */
    function delete($path) {
    	
        return unlink($path);
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
        
    	// restrict file name to 64 characters (maximum)
		if (strlen($name) > 64) {
			 $name = substr($name, 0, 64);
		}
    	
    	$name = preg_replace('`[^a-z0-9_-]`i', '_', $name);
        $name = preg_replace('`_{2,}`', '_', $name);
        
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
        $path .= $name;
        return $path;
    }
    
}

?>
