<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_Install
*/

require_once('Docman_View_ProjectHeader.class.php');

class Docman_View_Install extends Docman_View_ProjectHeader
{
    /* protected */ public function _content($params)
    {
        echo '<form action="' . $params['default_url'] . '" method="POST">';
        echo '<p>Do you want to install the docman now ?</p>';
        echo '<input type="hidden" name="action" value="install" />';
        echo '<input type="submit" name="confirm" value="Yes, install it now!" />';
        echo '<input type="submit" name="cancel" value="No, not for now." />';
        echo '</form>';
    }
}
