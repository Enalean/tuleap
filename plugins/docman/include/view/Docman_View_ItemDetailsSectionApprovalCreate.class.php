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

require_once('Docman_View_ItemDetailsSectionApproval.class.php');

require_once(dirname(__FILE__).'/../Docman_ApprovalTableFactory.class.php');

class Docman_View_ItemDetailsSectionApprovalCreate
extends Docman_View_ItemDetailsSectionApproval {

    function Docman_View_ItemDetailsSectionApprovalCreate(&$item, $url, $themePath) {
        parent::Docman_View_ItemDetailsSectionApproval($item, $url, $themePath, null);
    }

    function displayConfirmDelete() {
        $html = '';
        $html .= '<form action="'.$this->url.'" method="POST" class="docman_confirm_delete">';
        $html .= Docman::txt('details_approval_table_delete_txt');
        $html .= '<div class="docman_confirm_delete_buttons">';
        $html .= '<input type="hidden" name="action" value="approval_delete" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="submit" name="cancel" value="'.Docman::txt('details_delete_cancel').'" />';
        $html .= '<input type="submit" name="confirm" value="'.Docman::txt('details_delete_confirm').'" />';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }

    function displayNotificationEmail() {
        $html = '';
        $html .= '<h3>'.Docman::txt('details_approval_email_title').'</h3>';

        $atsm = new Docman_ApprovalTableNotificationCycle();
        $atsm->setItem($this->item);

        $atf = new Docman_ApprovalTableFactory($this->item->getId());
        $table = $atf->getTable(false);
        $atsm->setTable($table);

        $um =& UserManager::instance();
        $owner =& $um->getUserById($table->getOwner());
        $atsm->setOwner($owner);

        $email = $atsm->getNotifReviewer($owner);
        $html .= Docman::txt('details_approval_email_subject').' '.$email->getSubject()."\n";
        $html .= '<p class="docman_approval_email">';
        $html .= htmlentities($email->getBody());
        $html .= '</p>';
        $backurl = $this->url.'&action=approval_create&id='.$this->item->getId();
        $html .= '<a href="'.$backurl.'">'.Docman::txt('details_approval_email_back').'</a>';
        return $html;
    }

    function _getGlobalSettings() {
        $html = '';

        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>'.Docman::txt('details_approval_table_status').'</td>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED,
                      PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED,
                      PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED,
                      PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED);
        $txts = array(Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED),
                      Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED),
                      Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED),
                      Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED));
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'status', $this->table->getStatus(), false);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>'.Docman::txt('details_approval_table_description').'</td>';
        $html .= '<td>';
        $html .= '<textarea name="description">'.$this->table->getDescription().'</textarea>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        return $html;
    }

    function _getNotificationSettings() {
        $html = '';

        $html .= '<h3>'.Docman::txt('details_approval_notif_title').'</h3>';
        $html .= '<div class="docman_help">'.Docman::txt('details_approval_notif_help', $GLOBALS['sys_name']).'</div>';

        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>'.Docman::txt('details_approval_notif_type').'</td>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL);
        $txts = array(Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED),
                      Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED),
                      Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED));
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'notification', $this->table->getNotification(), false);
        $html .= '</td>';
        $html .= '</tr>';

        if($this->table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
            if($this->table->isEnabled()) {
                $html .= '<tr>';
                $html .= '<td>'.Docman::txt('details_approval_notif_relaunch').'</td>';
                $vals = array('no',
                              'yes');
                $txts = array($GLOBALS['Language']->getText('global', 'no'),
                              $GLOBALS['Language']->getText('global', 'yes'));
                $html .= '<td>'.html_build_select_box_from_arrays($vals, $txts, 'resend_notif', 'no', false).'</td>';
                $html .= '</tr>';
            }

            $html .= '<tr>';
            $html .= '<td>'.Docman::txt('details_approval_notif_email').'</td>';
            $html .= '<td>';
            $html .= '<a href="'.$this->url.'&action=approval_create&id='.$this->item->getId().'&section=view_notification_email">'.Docman::txt('details_approval_notif_email_act').'</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    function _getReviewerTable() {
        $html = '';
        $html .= '<h3>'.Docman::txt('details_approval_table_title').'</h3>';
        $html .= '<div class="docman_help">'.Docman::txt('details_approval_table_help').'</div>';

        $rIter = $this->table->getReviewerIterator();
        if($rIter !== null) {
            $docmanIcons =& $this->_getDocmanIcons();

            $html .= html_build_list_table_top(array(Docman::txt('details_approval_select'),
                                                     Docman::txt('details_approval_reviewer'),
                                                     Docman::txt('details_approval_review'),
                                                     Docman::txt('details_approval_rank')),
                                               false, false, false);
            $isFirst = true;
            $isLast  = false;
            $nbReviewers = $rIter->count();
            $i = 0;
            $rIter->rewind();
            while($rIter->valid()) {
                $isLast = ($i == ($nbReviewers - 1));

                $reviewer = $rIter->current();
                // i+1 to start with 'white'
                $html .= '<tr class="'.html_get_alt_row_color($i+1).'">';

                // Select
                $checkbox = '<input type="checkbox" name="sel_user[]" value="'.$reviewer->getId().'" />';
                $html .= '<td align="center">'.$checkbox.'</td>';

                // Username
                $html .= '<td>'.user_get_name_display_from_id($reviewer->getId()).'</td>';

                // Review
                $html .= '<td>'.$this->atf->getReviewStateName($reviewer->getState()).'</td>';

                // Rank
                $rank = $reviewer->getRank();
                $baseUrl  = '?group_id='.$this->item->getGroupId().'&action=approval_upd_user&id='.$this->item->getId().'&user_id='.$reviewer->getId().'&rank=';

                $begLink = '';
                $upLink  = '';
                if(!$isFirst) {
                    $begIcon = '<img src="'.$docmanIcons->getIcon('move-beginning').'" alt="Beginning" />';
                    $begLink = '<a href="'.$baseUrl.'beg">'.$begIcon.'</a>';
                    $upIcon  = '<img src="'.$docmanIcons->getIcon('move-up').'" alt="Up" />';
                    $upLink  = '<a href="'.$baseUrl.'up">'.$upIcon.'</a>';
                }
                $endLink  = '';
                $downLink = '';
                if(!$isLast) {
                    $endIcon  = '<img src="'.$docmanIcons->getIcon('move-end').'" alt="End" />';
                    $endLink  = '<a href="'.$baseUrl.'end">'.$endIcon.'</a>';
                    $downIcon = '<img src="'.$docmanIcons->getIcon('move-down').'" alt="Down" />';
                    $downLink = '<a href="'.$baseUrl.'down">'.$downIcon.'</a>';
                }

                $html .= '<td align="center">'.$upLink.'&nbsp;'.$downLink.'&nbsp;'.$begLink.'&nbsp;'.$endLink.'</td>';

                $html .= '</tr>';

                $isFirst= false;
                $i++;
                $rIter->next();
            }
            $html .= '</table>';

            // Action with selected reviewers
            $html .= '<p>';
            $html .= Docman::txt('details_approval_create_table_act');
            $vals = array('del',
                          'mail');
            $txts = array(Docman::txt('details_approval_create_table_act_rm'),
                          Docman::txt('details_approval_create_table_act_mail'));
            $html .= html_build_select_box_from_arrays($vals, $txts, 'sel_user_act', 100, true);
            $html .= '</p>';
        }
        return $html;
    }

    function _getAddReviewers() {
        $html = '';

        $ugroups = $this->atf->getUgroupsAllowedForTable($this->item->getGroupId());

        $html .= '<h3>'.Docman::txt('details_approval_create_reviewers_title').'</h3>';
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>'.Docman::txt('details_approval_create_reviewers_hand').'</td>';
        $html .= '<td><input type="text" name="user_list" value="" class="text_field"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>'.Docman::txt('details_approval_create_reviewers_ugroup').'</td>';
        $html .= '<td>'.html_build_multiple_select_box_from_array($ugroups, 'ugroup_list[]', array(), 8, true, '', false, '', false, '', false).'</td>';
        $html .= '</tr>';

        $html .= '</table>';
        return $html;
    }

    function displayUpdateForm() {
        $html = '';
        $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="hidden" name="action" value="approval_update" />';

        $html .= '<h3>'.Docman::txt('details_approval_create_settings').'</h3>';

        $this->atf = new Docman_ApprovalTableFactory($this->item->getId());
        $this->table = $this->atf->getTable();
        if($this->table !== null) {
            $html .= $this->_getGlobalSettings();
            $html .= $this->_getNotificationSettings();
            $html .= $this->_getReviewerTable();
        } else {
            $html .= '<p>'.Docman::txt('details_approval_create_no_reviewers').'</p>';
        }

        $html .= $this->_getAddReviewers();

        $html .= '<p>';
        $html .= '<td colspan="2"><input type="submit" value="'.Docman::txt('details_approval_create_submit').'"></td>';
        $html .= '</p>';

        $html .= '</form>';
        return $html;
    }

    function getContent() {
        $html = '';

        $html .= $this->getToolbar();

        $request =& HTTPRequest::instance();

        // Confirm table deletion
        if($request->exist('delete')) {
            if($request->get('delete') == 'confirm') {
                $html .= $this->displayConfirmDelete();
                return $html;
            }
        }

        // See notification email
        if($request->exist('section')) {
            switch($request->get('section')){
            case 'view_notification_email':
                $html .= $this->displayNotificationEmail();
                break;
            }
            return $html;
        }

        // Default update panel
        $html .= $this->displayUpdateForm();

        return $html;
    }

}

?>
