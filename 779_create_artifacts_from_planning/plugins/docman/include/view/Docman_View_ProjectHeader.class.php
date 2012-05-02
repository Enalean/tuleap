<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_ProjectHeader
*/

require_once('Docman_View_Header.class.php');

/* abstract */ class Docman_View_ProjectHeader extends Docman_View_Header {
    
    /* protected */ function _scripts($params) {
        echo '<script type="text/javascript"> var docman = new com.xerox.codendi.Docman('. $params['group_id'] .', ';
        $di =& $this->_getDocmanIcons($params);
        echo $this->phpArrayToJsArray(array_merge(array(
                'folderSpinner' => $di->getFolderSpinner(),
                'spinner'       => $di->getSpinner(),
                'pluginPath'    => $this->_controller->pluginPath,
                'themePath'     => $this->_controller->themePath,
                'language'      => array(
                    'btn_close'                => $GLOBALS['Language']->getText('global','btn_close'),
                    'new_in'                   => $GLOBALS['Language']->getText('plugin_docman','new_in'),
                    'new_other_folders'        => $GLOBALS['Language']->getText('plugin_docman','new_other_folders'),
                    'new_same_perms_as_parent' => $GLOBALS['Language']->getText('plugin_docman','new_same_perms_as_parent'),
                    'new_view_change'          => $GLOBALS['Language']->getText('plugin_docman','new_view_change'),
                    'new_news_explaination'    => $GLOBALS['Language']->getText('plugin_docman','new_news_explain'),
                    'new_news_displayform'     => $GLOBALS['Language']->getText('plugin_docman','new_news_displayform'),
                    'report_save_opt'          => $GLOBALS['Language']->getText('plugin_docman','report_save_opt'),
                    'report_custom_fltr'       => $GLOBALS['Language']->getText('plugin_docman','report_custom_fltr'),
                    'report_name_new'          => $GLOBALS['Language']->getText('plugin_docman','report_name_new'),
                    'report_name_upd'          => $GLOBALS['Language']->getText('plugin_docman','report_name_upd'),
                    'action_doc_id'            => $GLOBALS['Language']->getText('plugin_docman','action_doc_id'),
                    'action_newfolder'         => $GLOBALS['Language']->getText('plugin_docman','action_newfolder'),
                    'action_newdocument'       => $GLOBALS['Language']->getText('plugin_docman','action_newdocument'),
                    'action_details'           => $GLOBALS['Language']->getText('plugin_docman','action_details'),
                    'action_newversion'        => $GLOBALS['Language']->getText('plugin_docman','action_newversion'),
                    'action_move'              => $GLOBALS['Language']->getText('plugin_docman','action_move'),
                    'action_permissions'       => $GLOBALS['Language']->getText('plugin_docman','action_permissions'),
                    'action_history'           => $GLOBALS['Language']->getText('plugin_docman','action_history'),
                    'action_notifications'     => $GLOBALS['Language']->getText('plugin_docman','action_notifications'),
                    'action_delete'            => $GLOBALS['Language']->getText('plugin_docman','action_delete'),
                    'action_update'            => $GLOBALS['Language']->getText('plugin_docman','action_update'),
                    'action_cut'               => $GLOBALS['Language']->getText('plugin_docman','action_cut'),
                    'action_copy'              => $GLOBALS['Language']->getText('plugin_docman','action_copy'),
                    'action_paste'             => $GLOBALS['Language']->getText('plugin_docman','action_paste'),
                    'action_lock_add'          => $GLOBALS['Language']->getText('plugin_docman','action_lock_add'),
                    'action_lock_del'          => $GLOBALS['Language']->getText('plugin_docman','action_lock_del'),
                    'action_lock_info'         => $GLOBALS['Language']->getText('plugin_docman','action_lock_info'),
                    'action_approval'          => $GLOBALS['Language']->getText('plugin_docman','action_approval'),
                    'feedback_cut'             => $GLOBALS['Language']->getText('plugin_docman','info_cut_notify_cut'),
                    'feedback_copy'            => $GLOBALS['Language']->getText('plugin_docman','info_copy_notify_cp'),
                    'new_approvaltable'        => $GLOBALS['Language']->getText('plugin_docman','error_no_option')
                )
            ),
            $this->_getJSDocmanParameters($params)
        ));
        echo '); </script>';
    }
    
    /* protected */ function _getAdditionalHtmlParams($params) {
        return  array(
            'group'  => $params['group_id'],
            'toptab' => 'docman');
    }

    
    /* protected */ function _getJSDocmanParameters($params) {
        return array();
    }
}

?>
