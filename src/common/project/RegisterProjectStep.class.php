<?php

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep
* 
* A step during project registration. Each concrete subclass must provide 
* at least display() to display instruction/form to user.
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  author
*/
/* abstract */class RegisterProjectStep {
    var $name;
    var $help;
    function RegisterProjectStep($name, $help) {
        $this->name = $name;
        $this->help = $help;
    }
    /**
    * called before leaving this step
    * @return boolean post-requisites are valid
    */
    function onLeave($request, &$data) {
        return true;
    }
    /**
    * called before entering the step
    * @return boolean post-requisites are valid
    */
    function onEnter($request, &$data) {
        return true;
    }
    /**
    * display form/instructions to user
    */
    function display($data) {
    }
    /**
    * @return boolean data are valid for this step
    */
    function validate($data) {
        return true;
    }
}

?>
