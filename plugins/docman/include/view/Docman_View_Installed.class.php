<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_Installed
*/

require_once('Docman_View_ProjectHeader.class.php');

class Docman_View_Installed extends Docman_View_ProjectHeader
{
    /* protected */ public function _content($params)
    {
        echo '<h2>Congratulations!</h2>';
        echo '<p>You can now create folders and documents in <a href="' .  $params['default_url'] . '">your Advanced Document Manager</a>.<br />';
        echo 'Do not permissions to set permissions!</p>';
    }
}
