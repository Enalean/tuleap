<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Admin_View
*/

require_once('Docman_View_Extra.class.php');
require_once('www/project/admin/permissions.php');

class Docman_View_Admin_Permissions extends Docman_View_Extra {
    
    function _title($params) {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_permissions_title') .'</h2>';
    }
    function _content($params) {
        $content  = '';
        
        //{{{ Explanations
        $content .= '<div>';
        $content .= $GLOBALS['Language']->getText('plugin_docman', 'admin_permissions_instructions');
        $content .= '</div>';
        echo $content;
        //}}}
        
        $postUrl = $this->buildUrl($params['default_url'], array(
            'action' => 'admin_set_permissions'
        ));
        permission_display_selection_form("PLUGIN_DOCMAN_ADMIN", $params['group_id'], $params['group_id'], $postUrl);
    }
}

?>
