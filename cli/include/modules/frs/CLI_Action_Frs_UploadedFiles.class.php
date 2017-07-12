<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_UploadedFiles extends CLI_Action {
    function __construct() {
        parent::__construct('getUploadedFiles', 'Returns the list of files that are present in the incoming directory');
    }
}
