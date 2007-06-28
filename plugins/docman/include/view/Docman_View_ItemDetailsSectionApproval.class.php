<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

require_once('Docman_View_ItemDetailsSection.class.php');

require_once(dirname(__FILE__).'/../Docman_ApprovalTableFactory.class.php');

class Docman_View_ItemDetailsSectionApproval
extends Docman_View_ItemDetailsSection {
    var $table;
    var $atf;
    var $themePath;
    var $notificationManager;

    function Docman_View_ItemDetailsSectionApproval(&$item, $url, $themePath, $notificationManager) {
        parent::Docman_View_ItemDetailsSection($item, $url, 'approval', Docman::txt('details_approval'));

        $this->themePath = $themePath;
        $this->table = null;
        $this->atf   = null;
        $this->notificationsManager = $notificationManager;
    }

    function _getItemVersionLink($version) {
        $html = '';
        if($version !== null) {
            $title = '';
            $url   = '';

            $itemType = Docman_ItemFactory::getItemTypeForItem($this->item);
            if($itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
               || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                $vFactory =& new Docman_VersionFactory();
                $v = $vFactory->getSpecificVersion($this->item, $version);
                if($v) {
                    $url = Docman_View_View::buildUrl($this->url, 
                                                      array('action' => 'show',
                                                            'id'     => $this->item->getId(),
                                                            'version_number' => $v->getNumber()));
                    if($v->getLabel()) {
                        $title .= $v->getLabel().' - ';
                    }
                }
            }
            elseif($itemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $url = '/wiki/index.php?group_id='.$this->item->getGroupId().'&pagename='.$this->item->getPagename().'&version='.$version;
            }
            $title .= Docman::txt('details_approval_version_link').' '.$version;
            $html .= '<a href="'.$url.'">'.$title.'</a>';
        }
        return $html;
    }

    function comment2Html($txt, $groupId=null) {
        if($groupId === null) {
            $groupId = $this->item->getGroupId();
        }
        $sComment = htmlentities($txt);
        $comment = util_make_links($sComment, $groupId);
        $comment = nl2br($comment);
        return $comment;
    }

    function getReviewerTable($forceReadOnly = false) {
        $html = '';
        
        $rIter = $this->table->getReviewerIterator();
        if($rIter !== null) {
            $html .= '<h3>'.Docman::txt('details_approval_table_title').'</h3>';

            $user =& $this->_getCurrentUser();

            $html .= '<div class="docman_approval_form">';
            $html .= '<p><label>'.Docman::txt('details_approval_requester').'</label>';
            $html .= user_getrealname($this->table->getOwner()).'</p>';
            $html .= '<p><label>'.Docman::txt('details_approval_cycle_start_date').'</label>';
            $html .= util_timestamp_to_userdateformat($this->table->getDate(), true).'</p>';
            if($this->table->isClosed()) {
                $html .= '<p><label>'.Docman::txt('details_approval_table_status').'</label>';
                $html .= Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED);
                $html .= '</p>';
            }
            $html .= '<p><label>'.Docman::txt('details_approval_owner_comment').'</label><br />';
            $html .= '<em>'.$this->comment2Html($this->table->getDescription()).'</em>';
            $html .= '</p>';
            $html .= '</div>';

            $html .= html_build_list_table_top(array(Docman::txt('details_approval_reviewer'),
                                                     Docman::txt('details_approval_state'),
                                                     Docman::txt('details_approval_comment'),
                                                     Docman::txt('details_approval_date'),
                                                     Docman::txt('details_approval_version')));
            $userIsInTable = false;
            $rIter->rewind();
            while($rIter->valid()) {
                $reviewer = $rIter->current();

                $readOnly = true;
                $_trClass = ' class="docman_approval_readonly"';
                if(!$forceReadOnly && ($user->getId() == $reviewer->getId())) {
                    $_trClass = ' class="docman_approval_highlight"';
                    $readOnly = false;
                    $userIsInTable = true;
                }

                $html .= '<tr'.$_trClass.'>';

                // Name
                $html .= '<td>'.user_getrealname($reviewer->getId()).'</td>';

                // Review
                $_reviewHtml = $this->atf->getReviewStateName($reviewer->getState());
                if(!$readOnly) {
                    $_reviewHtml = '<a href="'.$this->url.'&action=details&id='.$this->item->getId().'&section=approval&user_id='.$reviewer->getId().'">'.$this->atf->getReviewStateName($reviewer->getState()).'</a>';
                }
                $html .= '<td>'.$_reviewHtml.'</td>';

                // Comment
                $html .= '<td>'.$this->comment2Html($reviewer->getComment()).'</td>';

                // Date
                $date = $reviewer->getReviewDate();
                $_dateHtml = '';
                if($date) {
                    $_dateHtml = util_timestamp_to_userdateformat($date, true);
                }
                $html .= '<td>'.$_dateHtml.'</td>';

                // Version
                $html .= '<td>'.$this->_getItemVersionLink($reviewer->getVersion()).'</td>';

                $html .= '</tr>';
                $rIter->next();
            }
            
            $html .= '</table>';
        }
        return $html;
    }
    

    function _getReviewCurrentVersion() {
        $version = null;
        $itemFactory = Docman_ItemFactory::instance($this->item->getGroupId());
        $itemType = $itemFactory->getItemTypeForItem($this->item);
        // Get current version for file, embeddedfile and wiki
        switch($itemType) {
        case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
        case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
            $currentVersion = $this->item->getCurrentVersion();
            $version = $currentVersion->getNumber();
            break;
        case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
            $version = $itemFactory->getCurrentWikiVersion($this->item);
            break;
        }
        return $version;
    }
    
    function getReviewForm($user) {
        $html = '';

        $itemCurrentVersion = $this->_getReviewCurrentVersion();

        $html .= '<h3>'.Docman::txt('details_approval_doc_review_title').'</h3>';
        $html .= '<div class="docman_approval_form">';
        
        // Doc title
        $html .= '<p><label>'.Docman::txt('details_approval_doc_review_name').'</label>';
        $html .= $this->item->getTitle();
        if($itemCurrentVersion == null) {
            $url = Docman_View_View::buildUrl($this->url, 
                                              array('action' => 'show',
                                                    'id'     => $this->item->getId()));
            $html .= ' - ';
            $html .= '<a href="'.$url.'">'.Docman::txt('details_approval_doc_review_link').'</a>';
        }
        $html .= '</p>';

        // Doc version
        $html .= '<p><label>'.Docman::txt('details_approval_doc_review_version').'</label>';
        if($itemCurrentVersion !== null) {
            $html .= $this->_getItemVersionLink($itemCurrentVersion);
        }
        else {
            $html .= Docman::txt('details_approval_doc_review_version_na');
        }
        $html .= '</p>';

        
        // Requester name
        $html .= '<p><label>'.Docman::txt('details_approval_requester').'</label>';
        $html .= user_getrealname($this->table->getOwner()).'</p>';

        // Cycle start date
        $html .= '<p><label>'.Docman::txt('details_approval_cycle_start_date').'</label>';
        $html .= util_timestamp_to_userdateformat($this->table->getDate(), true).'</p>';

        // Owner comment
        $html .= '<p><label>'.Docman::txt('details_approval_owner_comment').'</label><br />';
        $html .= '<em>'.$this->comment2Html($this->table->getDescription()).'</em>';
        $html .= '</p>';
        
        $html .= '</div>';

        $html .= '<h3>'.Docman::txt('details_approval_review_title').'</h3>';

        $reviewer = $this->atf->getReviewer($user->getId());

        $html .= '<form name="docman_approval_review" method="POST" action="?" class="docman_approval_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="hidden" name="action" value="approval_user_commit" />';
        if($itemCurrentVersion !== null) {
            // Add version here because someone can submit a new version while
            // current user is reviewing.
            $html .= '<input type="hidden" name="version" value="'.$itemCurrentVersion.'" />';
        }

        $html .= '<p><label>'.Docman::txt('details_approval_review_table').'</label>';
        $url = Docman_View_View::buildUrl($this->url, 
                                          array('action'  => 'details',
                                                'section' => 'approval',
                                                'id'     => $this->item->getId()));
        $html .= '<a href="'.$url.'">'.Docman::txt('details_approval_review_table_link').'</a>';
        $html .= '</p>';


        $html .= '<p><label>'.Docman::txt('details_approval_review_review').'</label>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
                      PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
                      PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED);
        $txts = array(Docman::txt('approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET),
                      Docman::txt('approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED),
                      Docman::txt('approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED));
        $html .= html_build_select_box_from_arrays($vals, $txts, 'state', $reviewer->getState(), false);
        $html .= '</p>';

        // If reviewer already approved or reject, display date
        if($reviewer->getReviewDate()) {
            $html .= '<p><label>'.Docman::txt('details_approval_review_date').'</label>';
            $html .= util_timestamp_to_userdateformat($reviewer->getReviewDate(), true);
            $html .= '</p>';
        }   

        // Review version
        $reviewVersion = $reviewer->getVersion();
        if($reviewVersion) {
            $html .= '<p><label>'.Docman::txt('details_approval_review_version').'</label>';
            $html .= $this->_getItemVersionLink($reviewVersion);
            if($reviewVersion != $itemCurrentVersion) {
                $html .= ' <strong>'.Docman::txt('details_approval_review_version_not_upd').'</strong>';
            }
            $html .= '</p>';
        }

        // Comment
        $html .= '<p><label>'.Docman::txt('details_approval_review_comment').'</label>';
        $html .= '<textarea name="comment">'.$reviewer->getComment().'</textarea></p>';
        
        // Notification
        $notifChecked  = !$user->isAnonymous() && $this->notificationsManager->exist($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $html .= '<p><label>'.Docman::txt('details_approval_review_notif').'</label>';
        $html .= '<input type="checkbox" name="monitor" value="1"'.$notifChecked.' />';
        $html .= Docman::txt('details_notifications_sendemail');
        $html .= '</p>';

        $html .= '<input type="submit" value="'.Docman::txt('details_approval_review_submit').'">';

        $html .= '</form>';

        return $html;
    }


    function getToolbar() {
        $html = '';
        $user =& $this->_getCurrentUser();
        $dpm  =& $this->_getPermissionsManager();
        if($dpm->userCanWrite($user, $this->item->getId())) {
            $url = $this->url.'&action=approval_create&id='.$this->item->getId();
            $adminLink = '<a href="'.$url.'">'.Docman::txt('details_approval_admin').'</a>';
            $html = '<strong>'.$adminLink.'</strong><br />';
        }
        return $html;
    }

    function getContent() {
        $html = '';

        $user =& $this->_getCurrentUser();
        $dpm  =& $this->_getPermissionsManager();
        if(!$dpm->userCanRead($user, $this->item->getId())) {
            return $html;
        }

        $html .= $this->getToolbar();

        $this->atf = new Docman_ApprovalTableFactory($this->item->getId());
        $this->table = $this->atf->getTable();
        if($this->table === null) {
            $html .= '<p>';
            $html .= Docman::txt('details_approval_no_table');
            if($dpm->userCanWrite($user, $this->item->getId())) {
                $url = $this->url.'&action=approval_create&id='.$this->item->getId();
                $adminLink = '<a href="'.$url.'">'.Docman::txt('details_approval_no_table_create').'</a>';
                $html .= ' <strong>'.$adminLink.'</strong><br />';
            }
            $html .= '</p>';
        }
        elseif($this->table->isDisabled()) {
            $html .= '<p>';
            $html .= Docman::txt('details_approval_not_available');
            $html .= '</p>';
        }
        else {
            $request =& HTTPRequest::instance();;
            if($request->exist('user_id')) {
                $user_id = (int) $request->get('user_id');
                if($user_id == $user->getId() 
                   && $this->atf->isReviewer($user->getId())
                   && $this->table->isEnabled()) {
                    $html .= $this->getReviewForm($user);
                }
            }
            else {
                $forceReadOnly = false;
                if($this->table->isClosed()) {
                    $forceReadOnly = true;
                }
                $html .= $this->getReviewerTable($forceReadOnly);
            }
        }

        return $html;
    }

    function &_getDocmanIcons() {
        $icons = new Docman_Icons($this->themePath.'/images/ic/');
        return $icons;
    }

    function &_getUserManager() {
        $um =& UserManager::instance();
        return $um;
    }

    function &_getCurrentUser() {
        $um   =& $this->_getUserManager();
        $user =& $um->getCurrentUser();
        return $user;
    }

    function &_getPermissionsManager() {
        $dpm =& Docman_PermissionsManager::instance($this->item->getGroupId());
        return $dpm;
    }

    function visitFolder($item, $params = array()) {
        return '';
    }

    function visitWiki($item, $params = array()) {
        return '';
    }

    function visitLink($item, $params = array()) {
        return '';
    }

    function visitFile($item, $params = array()) {
        return '';
    }

    function visitEmbeddedFile($item, $params = array()) {
        return '';
    }

    function visitEmpty($item, $params = array()) {
        return '';
    }
}

?>
