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
 * $Id$
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

        $atf = new Docman_ApprovalTableFactory($this->item->getId());
        $table = $atf->getTable(false);
        $email = $atf->getNotificationEmail($table->getOwner(), $table->getOwner(), $table->getNotification(), $table->getDescription());
        $html .= Docman::txt('details_approval_email_subject').' '.$email->getSubject()."\n";
        $html .= '<p class="docman_approval_email">';
        $html .= htmlentities($email->getBody());
        $html .= '</p>';
        $backurl = $this->url.'&action=approval_create&id='.$this->item->getId();
        $html .= '<a href="'.$backurl.'">'.Docman::txt('details_approval_email_back').'</a>';
        return $html;
    }
    
    function getContent() {
        $html = '';

        $html .= $this->getToolbar();

        $request =& HTTPRequest::instance();
        if($request->exist('delete')) {
            if($request->get('delete') == 'confirm') {
                $html .= $this->displayConfirmDelete();
                return $html;
            }
        }

        if($request->exist('section')) {
            switch($request->get('section')){
            case 'view_notification_email':
                $html .= $this->displayNotificationEmail();
                break;
            }
            return $html;
        }

        $html .= '<h3>'.Docman::txt('details_approval_create_settings').'</h3>';

        $atf = new Docman_ApprovalTableFactory($this->item->getId());
        $table = $atf->getTable();
        if($table !== null) {
            $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_approval_form">';
            $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
            $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
            $html .= '<input type="hidden" name="action" value="approval_update" />';
            
            // Settings
            $enableYesChecked = '';
            $enableNoChecked  = ' checked="checked"';
            if($table->isEnabled()) {
                $enableYesChecked = ' checked="checked"';
                $enableNoChecked  = '';
            }
            $html .= '<p><label>'.Docman::txt('details_approval_table_status').'</label>';
            $vals = array(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED,
                          PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED,
                          PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED);
            $txts = array(Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED),
                          Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED),
                          Docman::txt('details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED));
            $html .= html_build_select_box_from_arrays($vals, $txts, 'status', $table->getStatus(), false);
            $html .= '</p>';

            $html .= '<p><label>'.Docman::txt('details_approval_table_description').'</label>';
            $html .= '<textarea name="description">'.$table->getDescription().'</textarea>';
            $html .= '</p>';

            $html .= '<p><label>'.Docman::txt('details_approval_delete_table').'</label>';
            $html .= '<a href="'.$this->url.'&action=approval_create&delete=confirm&id='.$this->item->getId().'">'.Docman::txt('details_approval_delete_table_act').'</a>';
            $html .= '</p>';

            // Notification
            $html .= '<h3>'.Docman::txt('details_approval_notif_title').'</h3>';

            $html .= '<p>'.Docman::txt('details_approval_notif_help', $GLOBALS['sys_name']).'</p>';

            $html .= '<p><label>'.Docman::txt('details_approval_notif_type').'</label>';
            $vals = array(PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED,
                          PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE,
                          PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL);
            $txts = array(Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED),
                          Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED),
                          Docman::txt('details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED));
            $html .= html_build_select_box_from_arrays($vals, $txts, 'notification', $table->getNotification(), false);
            $html .= '</p>';

            if($table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
                if($table->isEnabled()) {
                    $html .= '<p><label>'.Docman::txt('details_approval_notif_relaunch').'</label>';
                    $html .= '<a href="'.$this->url.'&action=approval_notif_resend&id='.$this->item->getId().'">'.Docman::txt('details_approval_notif_relaunch_act').'</a>';
                    $html .= '</p>';
                }
                
                $html .= '<p><label>'.Docman::txt('details_approval_notif_email').'</label>';
                $html .= '<a href="'.$this->url.'&action=approval_create&id='.$this->item->getId().'&section=view_notification_email">'.Docman::txt('details_approval_notif_email_act').'</a>';
                $html .= '</p>';
            }

            $html .= '<p><input type="submit" value="'.Docman::txt('details_approval_notif_submit').'"></p>';
                        
            $html .= '</form>';
            
            $rIter = $table->getReviewerIterator();
            if($rIter !== null) {
                $html .= '<h3>'.Docman::txt('details_approval_table_title').'</h3>';

                $docmanIcons =& $this->_getDocmanIcons();

                $html .= html_build_list_table_top(array(Docman::txt('details_approval_reviewer'),
                                                         Docman::txt('details_approval_rank'),
                                                         Docman::txt('details_approval_delete')));
                $isFirst = true;
                $isLast  = false;
                $nbReviewers = $rIter->count();
                $i = 0;
                $rIter->rewind();
                while($rIter->valid()) {
                    $isLast = ($i == ($nbReviewers - 1));

                    $reviewer = $rIter->current();
                    $html .= '<tr>';

                    $html .= '<td>'.user_getrealname($reviewer->getId()).'</td>';
                    
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

                    $html .= '<td>'.$upLink.'&nbsp;'.$downLink.'&nbsp;'.$begLink.'&nbsp;'.$endLink.'</td>';
                   

                    $trashLink = '?group_id='.$this->item->getGroupId().'&action=approval_del_user&id='.$this->item->getId().'&user_id='.$reviewer->getId();
                    $trashWarn = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_delete_reviewer');
                    $trashAlt  = '';
                    $trash = html_trash_link($trashLink, $trashWarn, $trashAlt);
                    $html .= '<td>'.$trash.'</td>';

                    $html .= '</tr>';

                    $isFirst= false;
                    $i++;
                    $rIter->next();
                }
                $html .= '</table>';
            }
        }
        else {
            $html .= '<p>'.Docman::txt('details_approval_create_no_reviewers').'</p>';
        }
        

        $html .= '<h3>'.Docman::txt('details_approval_create_reviewers_title').'</h3>';

        $html .= '<form name="docman_approval_create" method="post" action="?" class="docman_approval_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="hidden" name="action" value="approval_add_user" />';

        $html .= '<p><label>'.Docman::txt('details_approval_create_reviewers_hand').'</label>';
        $html .= '<input type="text" name="user_list" value="" class="text_field"/>';
        $html .= '</p>';

        $ugroups = $atf->getUgroupsAllowedForTable($this->item->getGroupId());
        $html .= '<p><label>'.Docman::txt('details_approval_create_reviewers_ugroup').'</label>';
        $html .= html_build_select_box_from_arrays($ugroups['vals'], $ugroups['txts'], 'ugroup');
        $html .= '</p>';

        $html .= '<p><input type="submit" value="'.Docman::txt('details_approval_create_reviewers_submit').'"></p>';
        $html .= '</form>';

        return $html;
    }
    
}

?>
