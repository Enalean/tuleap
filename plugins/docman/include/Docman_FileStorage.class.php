<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 * FileStorage is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_FileStorage
{

    public $root;
    public function __construct($root)
    {
        $this->root       = $root;
    }

    public function upload($file, $group_id, $item_id, $version_number)
    {
        $path = $this->_getPath($file['name'], $group_id, $item_id, $version_number);
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $path;
        } else {
            return false;
        }
    }
    public function store($content, $group_id, $item_id, $version_number, $chunk_offset = 0, $chunk_size = 0)
    {
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

    public function getFileMD5sum($path)
    {
        if (is_file($path)) {
            return PHP_BigFile::getMd5Sum($path);
        } else {
            return false;
        }
    }

    public function copy($srcPath, $dst_name, $dst_group_id, $dst_item_id, $dst_version_number)
    {
        $dstPath = $this->_getPath($dst_name, $dst_group_id, $dst_item_id, $dst_version_number);

        if (copy($srcPath, $dstPath)) {
            return $dstPath;
        } else {
            return false;
        }
    }

    public function delete($path)
    {
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
    public function _getPath($name, $group_id, $item_id, $version_number)
    {
        $name = preg_replace('`[^a-z0-9_-]`i', '_', $name);
        $name = preg_replace('`_{2,}`', '_', $name);
        $hash1 = $item_id % 10;
        $hash2 = ( ($item_id - $hash1) / 10) % 10;

        $path_elements = array($this->root, $this->_getGroupName($group_id), $hash2, $hash1, $item_id, $version_number);
        $path = '';
        foreach ($path_elements as $elem) {
            $path .= $elem . '/';
            if (!is_dir($path)) {
                mkdir($path, 0700);
                chown($path, ForgeConfig::get('sys_http_user'));
                chgrp($path, ForgeConfig::get('sys_http_user'));
            }
        }

        $path .= $name;
        return $path;
    }

    public function _getGroupName($id)
    {
        $pm = ProjectManager::instance();
        $group = $pm->getProject($id);
        return $group->getUnixName();
    }
}
