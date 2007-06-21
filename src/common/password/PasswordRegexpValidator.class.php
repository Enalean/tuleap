<?php 

require_once('common/password/PasswordValidator.class.php');

/**
* PasswordRegexpValidator
* 
* Validate a password with a regexp
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
/* abstract */ class PasswordRegexpValidator extends PasswordValidator {
    
    var $regexp;
    
    /**
    * Constructor
    */
    function PasswordRegexpValidator($regexp, $description) {
        $this->PasswordValidator($description);
        $this->regexp = $regexp;
    }
    
    function validate($password) {
        return preg_match($this->regexp, $password);
    }
}
?>
