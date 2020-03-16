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

require_once(dirname(__FILE__) . '/../Docman_SettingsDao.class.php');
class Docman_View_Admin_View extends Docman_View_Extra
{

    public function _title($params)
    {
        echo '<h2>' . $this->_getTitle($params) . ' - ' . dgettext('tuleap-docman', 'Manage Display Preferences') . '</h2>';
    }
    public function _content($params)
    {
        $html = '';
        $html .= '<p>' . dgettext('tuleap-docman', 'Please select the default view for browsing documents. Please note that this setting can be overridden by user preferences.') . '</p>';
        $html .= '<form action="' . $params['default_url'] . '" method="POST">';
        $html .= '<select name="selected_view" onchange="this.form.submit()">';

        $sBo = Docman_SettingsBo::instance($params['group_id']);
        $actual = $sBo->getView();

        $html .= '<option value="Tree" ' . ($actual === 'Tree' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Tree');
        $html .= '</option>';
        $html .= '<option value="Icons" ' . ($actual === 'Icons' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Icons');
        $html .= '</option>';
        $html .= '<option value="Table" ' . ($actual === 'Table' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Table');
        $html .= '</option>';


        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="admin_change_view" />';
        $html .= '<noscript><input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></noscript>';
        echo $html;
    }

    /* protected */ public function _getSettingsDao()
    {
        $dao = new Docman_SettingsDao(CodendiDataAccess::instance());
        return $dao;
    }
}
