<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

/**
 * FileStorage is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_FileStorage {
    
    var $root;
    function Docman_FileStorage($root) {
        $this->root       = $root;
    }
    
    function upload($file, $group_id, $item_id, $version_number) {
        $path = $this->_getPath($file['name'], $group_id, $item_id, $version_number);
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $path;
        } else {
            return false;
        }
    }
    function store($content, $group_id, $item_id, $version_number, $chunk_offset = 0, $chunk_size = 0) {
        $path = $this->_getPath('file', $group_id, $item_id, $version_number);
        
        if (is_file($path)) {
            $mode = 'r+';
        } else {
            $mode = 'w';
        }
        
        if ($f = fopen($path, $mode)) {
            fseek($f, $chunk_offset * $chunk_size);
            
            if ($chunk_size > 0) {
                fwrite($f, $content, $chunk_size);
            } else {
                fwrite($f, $content);
            }
            
            fclose($f);
            return $path;
        } else {
            return false;
        }
    }
    
    function getFileMD5sum($group_id, $item_id, $version_number) {
        $path = $this->_getPath('file', $group_id, $item_id, $version_number);
        
        if (is_file($path)) {
            return md5_file($path);
        } else {
            return false;
        }
    }
    
    function copy($srcPath, $dst_name, $dst_group_id, $dst_item_id, $dst_version_number) {
        $dstPath = $this->_getPath($dst_name, $dst_group_id, $dst_item_id, $dst_version_number);
        
        if(copy($srcPath, $dstPath)) {
            return $dstPath;
        }
        else {
            return false;
        }
    }

    function delete($path) {
        return unlink($path);
    }
    /**
    *
    *  +----------------------------------------------------------------------------+
    *  |                            +----------------------------------------+      |
    *  |                 +------------------------------------------------+  |      |
    * _|__              _|_____    _|____________                         |  |      |
    * name              item_id    version_number                         v  v      v
    * Fichier.doc       567        10                 =>  group_name/6/7/567/10/Fichier_doc
    *                    ||                                          ^ ^
    *                    +-------------------------------------------+ |
    *                     +--------------------------------------------+
    *
    */
    function _getPath($name, $group_id, $item_id, $version_number) {
        $name = preg_replace('`[^a-z0-9_-]`i', '_', $name);
        $name = preg_replace('`_{2,}`', '_', $name);
        $hash1 = $item_id % 10;
        $hash2 = ( ($item_id - $hash1) / 10) % 10;
        
        $path_elements = array($this->root, $this->_getGroupName($group_id), $hash2, $hash1, $item_id, $version_number);
        $path = '';
        foreach($path_elements as $elem) {
            $path .= $elem .'/';
            if (!is_dir($path)) {
                mkdir($path, 0700);
            }
        }
        $path .= $name;
        return $path;
    }
    
    function _getGroupName($id) {
        $group = group_get_object($id);
        return $group->getUnixName();
    }
}

?>