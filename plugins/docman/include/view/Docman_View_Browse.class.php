<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Browse
*/

require_once('Docman_View_Display.class.php');

require_once(dirname(__FILE__).'/../Docman_SettingsBo.class.php');

/* abstract */ class Docman_View_Browse extends Docman_View_Display {
    
    /* protected */ function _mode($params) {

        // No mode selector in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $html = '<div style="text-align:right;float:right;">';
        $html .= '<form action="'. $params['default_url'] .'" method="POST">';
        $html .= '<select name="selected_view" onchange="this.form.submit()" style="font-family:monospace;">';
        $html .= '<option value="-1">'. $GLOBALS['Language']->getText('plugin_docman', 'browse_viewas') .'</option>';
        $actual = Docman_View_Browse::getViewForCurrentUser($params['group_id']);
        $views  = Docman_View_Browse::getAllViews();
        foreach($views as $view) {
            $html .= '<option value="'. $view .'">'. ($actual == $view ? '&gt;&nbsp;' : '&nbsp;&nbsp;') . $GLOBALS['Language']->getText('plugin_docman', 'view_'.$view) .'</option>';
        }
        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="change_view" />';
        $html .= '<input type="hidden" name="id" value="'. $params['item']->getId() .'" />';
        $html .= '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
        $html .= '</form>';
        $html .= '</div>';
        echo $html;
    }
    /* protected */ function _filter($params) {
        echo '<!-- Filter -->';
    }
    function getActionOnIconForFolder() {
        return 'show';
    }
    function getClassForFolderLink() {
        return '';
    }
    
    /* static */ function getItemClasses($params) {
        $li_classes = 'docman_item';
        if (isset($params['is_last']) && $params['is_last']) {
            $li_classes .= '_last';
        }
        return $li_classes;
    }
    /* static */ function isViewAllowed($view) {
        //List is part of SOAP api
        return in_array($view, array_merge(Docman_View_Browse::getAllViews(), array('List')));
    }
    /* static */ function getViewForCurrentUser($group_id, $report = '') {
        if($report != '') {
            $pref = $report;
        }
        else {
            $pref = user_get_preference(PLUGIN_DOCMAN_VIEW_PREF .'_'. $group_id);
            if (!$pref) {
                $sBo =& Docman_SettingsBo::instance($group_id);
                $pref = $sBo->getView();            
            }
        }
        if (!$pref || !Docman_View_Browse::isViewAllowed($pref)) {
            $pref = 'Tree';
        }
        return $pref;
    }
    /* static */ function getAllViews() {
        return array('Tree', 'Icons', 'Table');
    }
}

?>
