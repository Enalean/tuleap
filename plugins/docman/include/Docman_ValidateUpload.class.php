<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_ValidateUpload
*/

require_once('Docman_Validator.class.php');

class Docman_ValidateUpload extends Docman_Validator {
    function Docman_ValidateUpload(&$request) {
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