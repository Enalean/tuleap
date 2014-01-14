<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Proftpd_Directory_DirectoryParser {

    /**
     * Parse the content of a given directory
     *
     * @param  String $path The path to parse
     *
     * @return Proftpd_Directory_DirectoryItem[]
     */
    public function parseDirectory($path) {
        $items                = array();
        $directory_file_names = scandir($path);

        foreach ($directory_file_names as $file_name) {
            $full_file_name = $this->getFullFileName($path, $file_name);

            $items[] = new Proftpd_Directory_DirectoryItem(
                $file_name,
                filetype($full_file_name),
                filesize($full_file_name),
                filemtime($full_file_name)
            );
        }

        return $items;
    }

    private function getFullFileName($path, $file_name) {
        if (substr($path, -1) === '/') {
            return $path.$file_name;
        }

        return $path.'/'.$file_name;
    }
}

?>
