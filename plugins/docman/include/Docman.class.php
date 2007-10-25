<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * 
 */
require_once('DocmanController.class.php');
require_once('DocmanActions.class.php');
class Docman extends DocmanController {

    function Docman(&$plugin, $pluginPath, $themePath) {
        $this->DocmanController($plugin, $pluginPath, $themePath, HTTPRequest::instance());
    }


    /* protected */ function _checkBrowserCompliance() {
        if($this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'docman_browserns4'));
        }
    }

    /* protected */ function _includeView() {
        $className = 'Docman_View_'. $this->view;
        if(file_exists(dirname(__FILE__).'/view/'. $className .'.class.php')) {
            require_once('view/'. $className .'.class.php');
            return $className;
        }
        return false;
    }
    /* protected */ function _set_deleteView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_redirectView() {
        if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
            $this->_viewParams['redirect_to'] = $redirect_to;
        }
        $this->view = 'RedirectAfterCrud';
    }
    /* protected */ function _setView($view) {
        if ($view == 'getRootFolder') {
            $this->feedback->log('error', 'Unable to process request');
            $this->_set_redirectView();
        } else {
            $this->view = $view;
        }
    }
    /* protected */ function _set_moveView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view) {
    	   $this->view = $item->accept($get_show_view, $this->request->get('report'));
    }
    /* protected */ function _set_createItemView_afterCreate($view) {
        if ($view == 'createFolder') {
            $this->view = 'NewFolder';
        } else {
            $this->view = 'NewDocument';
        }
    }

    /**
     * Get the list of all futur obsolete documents and warn document owner
     * about this obsolescence.
     */
    function notifyFuturObsoleteDocuments() {
        $itemFactory = new Docman_ItemFactory(0);

        //require_once('common/mail/TestMail.class.php');
        //$mail = new TestMail();
        //$mail->_testDir = '/local/vm16/codev/servers/docman-2.0/var/spool/mail';
        $mail = new Mail();

        $itemIter = $itemFactory->findFuturObsoleteItems();
        $itemIter->rewind();
        while($itemIter->valid()) {
            $item =& $itemIter->current();

            // Users
            $um    =& UserManager::instance();
            $owner =& $um->getUserById($item->getOwnerId());
            
            // Project
            $group = group_get_object($item->getGroupId());
            
            // Date
            $obsoDate = util_timestamp_to_userdateformat($item->getObsolescenceDate(), true);
            
            // Urls
            $baseUrl = get_server_url().$this->pluginPath.'/index.php?group_id='.$item->getGroupId().'&id='.$item->getId();
            $directUrl = $baseUrl .'&action=show';
            $detailUrl = $baseUrl .'&action=details';
            
            $subj = $this->txt('obso_warn_email_subject', array($GLOBALS['sys_name'],
                                                                $item->getTitle()));
            $body = $this->txt('obso_warn_email_body', array($item->getTitle(),
                                                             $group->getPublicName(),
                                                             $obsoDate,
                                                             $directUrl,
                                                             $detailUrl));
            
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setTo($owner->getEmail());
            $mail->setSubject($subj);
            $mail->setBody($body);
            $mail->send();
            
            $itemIter->next();
        }
    }
}

?>
