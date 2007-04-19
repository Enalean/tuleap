<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_UploadedFiles extends CLI_Action {
    function CLI_Action_Frs_UploadedFiles() {
        $this->CLI_Action('getUploadedFiles', 'Returns the list of files that are present in the incoming directory');
    }
}

?>
