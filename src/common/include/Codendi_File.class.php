<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
class Codendi_File {
    
    /**
     * No need to create an object from this class for now
     */
    protected function __construct() {}
    
    /**
     * Tell if $file is a file
     * Handle big files (is_file() doesn't)
     * @see http://us3.php.net/manual/fr/function.is-file.php#81316
     *
     * @param string $file Path to the file
     *
     * @return bool true if $file is a file
     */
    public static function isFile($file) {
        exec('[ -f ' . escapeshellarg($file) . ' ]', $tmp, $ret);
        return $ret == 0;
    }
    
    /**
     * Return the filesize of the file
     * handle big files (filesize() doesn't)
     * @see http://us3.php.net/manual/fr/function.filesize.php#80959
     * 
     * @param string $file Path to the file
     *
     * @return int the size of the file $file
     */
    public static function getSize($file) {
        $size = @filesize($file);
        if ($size === false) {
            $size = trim(shell_exec('stat -c%s ' . escapeshellarg($file)));
        }
        return $size;
    }
}
