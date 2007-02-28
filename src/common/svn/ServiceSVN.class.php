<?php
/**
* ServiceSVN
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class ServiceSVN extends Service {
    function isRequestedPageDistributed(&$request) {
        return ($_SERVER['SCRIPT_NAME'] == '/svn/viewvc.php')
            || ($_SERVER['SCRIPT_NAME'] == '/svn/index.php' && $request->exist('func') && $request->get('func') != 'info')
            || ($_SERVER['SCRIPT_NAME'] == '/svn/admin/index.php' && $request->exist('func') && $request->get('func') == 'access_control');
    }
}
?>
