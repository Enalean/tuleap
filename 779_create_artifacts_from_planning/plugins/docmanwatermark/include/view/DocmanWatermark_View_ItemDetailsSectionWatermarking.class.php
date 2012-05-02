<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 * 
 */

require_once dirname(__FILE__).'/../../../docman/include/view/Docman_View_ItemDetailsSection.class.php';
require_once dirname(__FILE__).'/../DocmanWatermark_ItemFactory.class.php';
require_once dirname(__FILE__).'/../DocmanWatermark_Log.class.php';

class DocmanWatermark_View_ItemDetailsSectionWatermarking extends Docman_View_ItemDetailsSection {
    
    function __construct($item, $url) {
        parent::__construct($item, $url, 'watermarking', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_tab_title'));
    }
    
    function getContent() {
        $html = '';
        $dwItemFactory = $this->getDocmanWatermarkItemFactory();
        $watermarkingIsDisabled = $dwItemFactory->isWatermarkingDisabled($this->item->getId());

        // Status
        if ($watermarkingIsDisabled) {
            $status = '<strong>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_disabled').'</strong>';
        } else {
            $status = $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_enabled');
        }
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_status', array($status)).'</p>';
        
        // About section
        $html .= '<h2>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_about_title').'</h2>';
        $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_disclamer_pdf').'</p>';
        
        // Disable/enable section
        $user = $this->getUserManager()->getCurrentUser();
        $dPm = $this->getDocman_PermissionsManager($this->item->getGroupId());
        if ($dPm->userCanManage($user, $this->item->getId())) {
            $html .= '<h2>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_toggle_title').'</h2>';
            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_desc').'</p>';
            $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_clicktoproceed').'</p>';
            $html .= '<form method="post" action="'.$this->url.'&item_id='.$this->item->getId().'&action=docmanwatermark_toggle_item">';
            if ($watermarkingIsDisabled) {
                $html .= '<input type="submit" name="enable_watermarking" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_enableit').'" />';
            } else {
                $html .= '<input type="submit" name="disable_watermarking" value="'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_watermarking_disableit').'" />';
            }
            $html .= '</form>';
        }
        
        // History
        $html .= '<h2>'.$GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_title').'</h2>';
        $dwLog = $this->getDocmanWatermark_Log();
        $dar = $dwLog->getLog($this->item);
        if ($dar && $dar->rowCount() > 0) {
            $uh = UserHelper::instance();
            $hp = Codendi_HTMLPurifier::instance();
            $titles = array($GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_when'),
                            $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_who'),
                            $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_what'));
            $html .=  html_build_list_table_top($titles, false, false, false);
            $altColor = 0;
            foreach($dar as $logEntry) {
                $html .= '<tr class="'.html_get_alt_row_color($altColor++).'">';
                $html .= '<td>'.util_timestamp_to_userdateformat($logEntry['time']).'</td>';
                $html .= '<td>'.$hp->purify($uh->getDisplayNameFromUserId($logEntry['who'])).'</td>';
                $html .= '<td>'.(($logEntry['watermarked'] == 0) ? $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_desactivate') : $GLOBALS['Language']->getText('plugin_docmanwatermark', 'details_history_activate')).'</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }
    
    /**
     * Wrapper for DocmanWatermark_ItemFactory
     * 
     * @return DocmanWatermark_ItemFactory
     */
    function getDocmanWatermarkItemFactory() {
        return new DocmanWatermark_ItemFactory();
    }
    
    /**
     * Wrapper for DocmanWatermark_Log
     * 
     * @return DocmanWatermark_Log
     */
    function getDocmanWatermark_Log() {
        return new DocmanWatermark_Log();
    }
    
    /**
     * Wrapper for Docman_PermissionsManager
     * 
     * @param Integer $groupId
     * 
     * @return Docman_PermissionsManager
     */
    function getDocman_PermissionsManager($groupId) {
        return Docman_PermissionsManager::instance($groupId);
    }
    
    /**
     * Wrapper for UserManager
     * 
     * @return UserManager
     */
    function getUserManager() {
        return UserManager::instance();
    }
}
?>