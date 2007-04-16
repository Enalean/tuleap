<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */
require_once('Docman_View_ItemDetailsSection.class.php');

class Docman_View_ItemDetailsSectionNotifications extends Docman_View_ItemDetailsSection {
    var $notificationsManager;
    var $token;
    function Docman_View_ItemDetailsSectionNotifications(&$item, $url, &$notificationsManager, $token) {
        parent::Docman_View_ItemDetailsSection($item, $url, 'notifications', $GLOBALS['Language']->getText('plugin_docman', 'details_notifications'));
        $this->notificationsManager =& $notificationsManager;
        $this->token = $token;
    }
    function getContent() {
        $content = '<dl><dt>'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications') .'</dt>';
        $content .= '<dd>';
        $content .= '<form action="" method="POST">';
        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="'. $this->token .'" />';
        }
        $content .= '<input type="hidden" name="action" value="monitor" />';
        $content .= '<input type="hidden" name="id" value="'. $this->item->getId() .'" />';
        $um   =& UserManager::instance();
        $user =& $um->getCurrentUser();
        $checked  = !$user->isAnonymous() && $this->notificationsManager->exist($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $disabled = $user->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="monitor" value="0" />';
        $content .= '<input type="checkbox" name="monitor" value="1" id="plugin_docman_monitor_item" '. $checked .' '. $disabled .' />';
        $content .= '<label for="plugin_docman_monitor_item">'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_sendemail') .'</label>';
        $content .= '</p>';
        $content .= $this->item->accept($this, array('user' => &$user));
        $content .= '<p><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        $content .= '</form>';
        $content .= '</dd></dl>';
        return $content;
    }
    
    function visitEmpty(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitWiki(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params) {
        return $this->visitDocument($item, $params);
    }
    function visitDocument(&$item, $params) {
        return '';
    }
    function visitFolder(&$item, $params) {
        $content = '<blockquote>';
        $checked  = !$params['user']->isAnonymous() && $this->notificationsManager->exist($params['user']->getId(), $this->item->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE) ? 'checked="checked"' : '';
        $disabled = $params['user']->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="cascade" value="0" />';
        $content .= '<input type="checkbox" name="cascade" value="1" id="plugin_docman_monitor_cascade_item" '. $checked .' '. $disabled .' />';
        $content .= '<label for="plugin_docman_monitor_cascade_item">'. $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_cascade_sendemail') .'</label>';
        $content .= '</blockquote>';
        return $content;
    }
}

?>
