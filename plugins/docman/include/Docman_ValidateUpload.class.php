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


require_once('Docman_Validator.class.php');

class Docman_ValidateUpload extends Docman_Validator {
    function __construct(&$request) {
        if (!$request->exist('upload_content')) {
            $ok = false;
            if (isset($_FILES['file'])) {
                switch($_FILES['file']['error']) {
                    case UPLOAD_ERR_OK:
                        // all is OK
                        $ok = true;
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_size', $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_partial', $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_nofile', $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_notmp', $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_cantwrite', $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_extension', $_FILES['file']['error']));
                        break;
                    default:
                        $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload_unknown', $_FILES['file']['error']));
                }
            }
            if (!isset($_FILES['file']) || ($ok && $_FILES['file']['name'] == '')) {
                $this->addError($GLOBALS['Language']->getText('plugin_docman', 'error_upload'));
            }
        }
    }
}

?>