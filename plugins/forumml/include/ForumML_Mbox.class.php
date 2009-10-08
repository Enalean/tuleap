<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require 'Mail/Mbox.php';

class ForumML_Mbox extends Mail_Mbox {

    /**
     * Open the mbox file
     *
     * Also, this function will process the Mbox and create a cache
     * that tells each message start and end bytes.
     *
     * @return boolean|PEAR_Error True if all went ok, PEAR_Error on failure
     * @access public
     */
    function open($create = false)
    {
        // check if file exists else return pear error
        if (!is_file($this->_file)) {
            if ($create) {
                $ret = $this->_create();
                if (PEAR::isError($ret)) {
                    return $ret;
                }
            } else {
                return PEAR::raiseError(
                    'Cannot open the mbox file "'
                    . $this->_file . '": file does not exist.',
                    MAIL_MBOX_ERROR_FILE_NOT_EXISTING
                );
            }
        }

        // opening the file
        $this->_lastModified = filemtime($this->_file);
        $this->_resource     = fopen($this->_file, 'r');
        if (!is_resource($this->_resource)) {
            return PEAR::raiseError(
                'Cannot open the mbox file: maybe without permission.',
                MAIL_MBOX_ERROR_NO_PERMISSION
            );
        }

        // process the file and get the messages bytes offsets
        $this->_process();

        return true;
    }

}

?>
