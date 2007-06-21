<?php 
/**
* PasswordValidator
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
/* abstract */ class PasswordValidator {
    var $description;
    /**
    * PasswordValidator
    * 
    * @param  description  
    */
    function PasswordValidator($description) {
        $this->description = $description;
    }
    
    /**
    * validate
    * 
    * @return boolean true if the password is valid
    *
    */
    /* abstract */ function validate($pwd) {
        return false;
    }
    
    /**
    * description
    * 
    * @return string descrption of the validator
    *
    */
    function description() {
        return $this->description;
    }
    
}
?>
