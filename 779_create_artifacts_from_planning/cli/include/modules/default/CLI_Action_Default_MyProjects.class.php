<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Default_MyProjects extends CLI_Action {
    function CLI_Action_Default_MyProjects() {
        $this->CLI_Action('myprojects', 'Get the projects the current user is member of');
        $this->setSoapCommand('getMyProjects');
    }
    
    function addProjectParam() {
    }

}

?>