<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_Admin_View
*/

require_once('Docman_View_Extra.class.php');
require_once('Docman_View_Browse.class.php');

require_once(dirname(__FILE__).'/../Docman_SettingsDao.class.php');
class Docman_View_Admin_View extends Docman_View_Extra
{

    function _title($params)
    {
        echo '<h2>'. $this->_getTitle($params) .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_view_title') .'</h2>';
    }
    function _content($params)
    {
        $html = '';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_view_instructions') .'</p>';
        $html .= '<form action="'. $params['default_url'] .'" method="POST">';
        $html .= '<select name="selected_view" onchange="this.form.submit()">';

        $sBo = Docman_SettingsBo::instance($params['group_id']);
        $actual = $sBo->getView();

        $views  = Docman_View_Browse::getDefaultViews();
        foreach ($views as $view) {
            $html .= '<option value="'. $view .'" '. ($actual == $view ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('plugin_docman', 'view_'.$view) .'</option>';
        }
        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="admin_change_view" />';
        $html .= '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
        echo $html;
    }

    /* protected */ function _getSettingsDao()
    {
        $dao = new Docman_SettingsDao(CodendiDataAccess::instance());
        return $dao;
    }
}
