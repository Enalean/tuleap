<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin extends Docman_View_Extra
{

    function _title($params)
    {
        echo '<h2>'. $GLOBALS['Language']->getText('plugin_docman', 'service_lbl_key') .' - '. $GLOBALS['Language']->getText('plugin_docman', 'admin_title') .'</h2>';
    }
    function _content($params)
    {
        $html = '';
        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_permissions')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_permissions_title') .'</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_permissions_descr') .'</p>';

        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_view')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_view_title') .'</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_view_descr') .'</p>';

        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_metadata')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_title') .'</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_metadata_descr') .'</p>';

        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'report_settings')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_report_title') .'</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_report_descr') .'</p>';

        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_obsolete')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_obsolete_title') .'</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_obsolete_descr') .'</p>';

        $html .= '<h3><a href="'. DocmanViewURLBuilder::buildUrl($params['default_url'], array('action' => 'admin_lock_infos')) .'">'. $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_title'). '</a></h3>';
        $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'admin_lock_infos_descr') .'</p>';

        echo $html;
    }
}
