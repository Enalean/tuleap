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

require_once 'Codendi_File.class.php';
require_once('lib/PHP_BigFile.class.php');

if (file_exists("/usr/share/pear/HTTP/Download.php")) {
  include_once 'HTTP/Download.php';    //do not stop script if pear package is not installed
}
if (class_exists('HTTP_Download')) { //to be removed in 4.2 (replace by a require_once)
    
/** 
 * Send HTTP Downloads/Responses. Based on HTTP_Download from PEAR
 *
 * Handle big files (>4Gb)
 */
class Codendi_HTTP_Download extends HTTP_Download {
    
    
    /**
     * Set path to file for download
     *
     * The Last-Modified header will be set to files filemtime(), actually.
     * Returns PEAR_Error (HTTP_DOWNLOAD_E_INVALID_FILE) if file doesn't exist.
     * Sends HTTP 404 status if $send_404 is set to true.
     * 
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     * @param   string  $file       path to file for download
     * @param   bool    $send_404   whether to send HTTP/404 if
     *                              the file wasn't found
     */
    function setFile($file, $send_404 = true)
    {
        $file = realpath($file);
        if (!Codendi_File::isFile($file)) {
            if ($send_404) {
                $this->HTTP->sendStatusCode(404);
            }
            return PEAR::raiseError(
                "File '$file' not found.",
                HTTP_DOWNLOAD_E_INVALID_FILE
            );
        }
        $this->setLastModified(filemtime($file));
        $this->file = PHP_BigFile::stream($file);
        $this->size = Codendi_File::getSize($file);
        return true;
    }   

    /**
     * Static send
     *
     * @see     HTTP_Download::staticSend()
     * 
     * @static
     * @access  public
     * @return  mixed   Returns true on success or PEAR_Error on failure.
     * @param   array   $params     associative array of parameters
     * @param   bool    $guess      whether HTTP_Download::guessContentType()
     *                               should be called
     */
    function staticSend($params, $guess = false)
    {
        $d = new Codendi_HTTP_Download();
        $e = $d->setParams($params);
        if (PEAR::isError($e)) {
            return $e;
        }
        if ($guess) {
            $e = $d->guessContentType();
            if (PEAR::isError($e)) {
                return $e;
            }
        }
        return $d->send();
    }
}

}
?>
