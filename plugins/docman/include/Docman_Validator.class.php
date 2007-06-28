<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Validator
*/

class Docman_Validator {
    var $_errors;
    function addError($error) {
        if (!$this->_errors) {
            $this->_errors = array();
        }
        $this->_errors[] = $error;
    }
    function getErrors() {
        return $this->_errors;
    }
    function isValid() {
        return count($this->_errors) ? false : true;
    }
}
class Docman_ValidatePresenceOf extends Docman_Validator {
    function Docman_ValidatePresenceOf($data, $field, $msg) {
        if (!$data || !isset($data[$field]) || trim($data[$field]) == '') {
            $this->addError($msg);
        }
    }
}

class Docman_ValidateValueNotEmpty extends Docman_Validator {
    function Docman_ValidateValueNotEmpty($value, $msg) {
        if(!$value || $value === null || $value == '') {
            $this->addError($msg);
        }
    }
}

?>