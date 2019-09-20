<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleeap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class Docman_View_ItemDetailsSectionApprovalCreate extends Docman_View_ItemDetailsSectionApproval
{

    function __construct($item, $url, $themePath)
    {
        parent::__construct($item, $url, $themePath, null);
    }

    function displayConfirmDelete()
    {
        $html = '';
        $html .= '<form action="'.$this->url.'" method="POST" class="docman_confirm_delete">';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_delete_txt');
        $html .= '<div class="docman_confirm_delete_buttons">';
        $html .= '<input type="hidden" name="action" value="approval_delete" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        if ($this->version !== null) {
            $html .= '<input type="hidden" name="version" value="'.$this->version.'" />';
        }
        $html .= '<input type="submit" name="cancel" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_delete_cancel').'" />';
        $html .= '<input type="submit" name="confirm" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_delete_confirm').'" />';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }

    function displayNotificationEmail()
    {
        $html = '';
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_email_title').'</h3>';

        $atsm = new Docman_ApprovalTableNotificationCycle(
            new MailNotificationBuilder(
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        UserManager::instance(),
                        new ProjectAccessChecker(
                            PermissionsOverrider_PermissionsOverriderManager::instance(),
                            new RestrictedUserCanAccessProjectVerifier(),
                            EventManager::instance()
                        ),
                        new MailLogger()
                    )
                )
            )
        );
        $atsm->setItem($this->item);

        $atf = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item);
        $table = $atf->getTable(false);
        $atsm->setTable($table);

        $um = UserManager::instance();
        $owner = $um->getUserById($table->getOwner());
        $atsm->setOwner($owner);

        $atsm->sendNotifReviewer($owner);
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_email_subject').' '.$atsm->getNotificationSubject()."\n";
        $html .= '<p class="docman_approval_email">';

        if (ProjectManager::instance()->getProject($this->item->getGroupId())->getTruncatedEmailsUsage()) {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'truncated_email');
        } else {
            $html .= htmlentities(quoted_printable_decode($atsm->getNotificationBodyText()), ENT_COMPAT, 'UTF-8');
        }
        $html .= '</p>';
        $backurl = $this->url.'&action=approval_create&id='.$this->item->getId();
        $html .= '<a href="'.$backurl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_email_back').'</a>';
        return $html;
    }

    function _getNewTable()
    {
        $html = '';
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $lastDocumentVersion = $this->atf->getLastDocumentVersionNumber();
            if ($this->table->getVersionNumber() < $lastDocumentVersion) {
                $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_new_title').'</h3>';
                $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_new_desc', array($this->table->getVersionNumber(), $lastDocumentVersion)).'</p>';
                $html .= $this->displayImportLastTable(true);
            }
        }
        return $html;
    }

    function _getGlobalSettings()
    {
        $html = '';

        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_settings').'</h3>';
        $html .= '<div id="docman_approval_table_create_settings">';
        $html .= '<table>';

        // Version
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_version').'</td>';
            $html .= '<td>';
            $html .= $this->table->getVersionNumber();
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Owner
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_requester').'</td>';
        $html .= '<td>';
        $html .= '<input type="text" class="text_field" name="table_owner" value="'.user_getname($this->table->getOwner()).'" />';
        $html .= '</td>';
        $html .= '</tr>';

        // Status
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_status').'</td>';
        $vals = array(0 => PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED,
                      1 => PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED);
        $txts = array(0 => $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED),
                      1 => $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED));
        if ($this->table->isCustomizable()) {
            $vals[2] = PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED;
            $vals[3] = PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED;
            $txts[2] = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
            $txts[3] = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        }
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'status', $this->table->getStatus(), false);
        $html .= '</td>';
        $html .= '</tr>';

        // Description
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_description').'</td>';
        $html .= '<td>';
        $html .= '<textarea name="description">'.$this->hp->purify($this->table->getDescription()).'</textarea>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    function _getNotificationSettings()
    {
        $html = '';

        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_title').'</h3>';
        $html .= '<div id="docman_approval_table_create_notification">';
        if (!$this->table->isClosed()) {
            $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_help', $GLOBALS['sys_name']).'</div>';
        }
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_type').'</td>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL);
        $txts = array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED),
                      $GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED),
                      $GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED));
        $html .= '<td>';
        if (!$this->table->isClosed()) {
            $html .= html_build_select_box_from_arrays($vals, $txts, 'notification', $this->table->getNotification(), false);
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_'.$this->table->getNotification());
        }
        $html .= '</td>';
        $html .= '</tr>';

        if (!$this->table->isClosed()) {
            if ($this->table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
                if ($this->table->isEnabled()) {
                    $html .= '<tr>';
                    $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_relaunch').'</td>';
                    $vals = array('no',
                                  'yes');
                    $txts = array($GLOBALS['Language']->getText('global', 'no'),
                                  $GLOBALS['Language']->getText('global', 'yes'));
                    $html .= '<td>'.html_build_select_box_from_arrays($vals, $txts, 'resend_notif', 'no', false).'</td>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_email').'</td>';
                $html .= '<td>';
                $html .= '<a href="'.$this->url.'&action=approval_create&id='.$this->item->getId().'&section=view_notification_email">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_email_act').'</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        $html .= $this->_displayNotificationOccurence();
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    function _displayNotificationOccurence()
    {
        $html = '<tr>';
        $html .= '<td>';
        $html .= '<h4>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_occurence_title').'</h4>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>';
        $occurence = $this->table->getNotificationOccurence();
        if ($occurence) {
            $checked = 'checked="true"';
        } else {
            $checked = '';
        }
        $html .= '<span id="approval_table_reminder" ></span><input id="approval_table_reminder_checkbox" type="checkbox" name="reminder" '.$checked.' /></span>';
        $html .= ' ';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_send_to_approvers');
        $html .= ' </td>';
        $html .= ' </tr>';
        $html .= ' <tr>';
        $html .= '<td>';
        $html .= '<span id="approval_table_occurence_form" > '.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_every');
        $html .= '<input size="2" name="occurence" value="'.$occurence.'" /> ';
        $html .= html_build_select_box_from_arrays(array(1, 7), array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_days'), $GLOBALS['Language']->getText('plugin_docman', 'details_approval_weeks')), 'period', null, false);
        $html .= '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    function _getReviewerTable()
    {
        $html  = '';
        $uh    = UserHelper::instance();
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_title').'</h3>';
        $html .= '<div id="docman_approval_table_create_table">';
        if (!$this->table->isClosed()) {
            $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_help').'</div>';
        }
        $rIter = $this->table->getReviewerIterator();
        if ($rIter !== null) {
            $docmanIcons = $this->_getDocmanIcons();

            $html .= html_build_list_table_top(
                array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_select'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_reviewer'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_review'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_rank')),
                false,
                false,
                false
            );
            $isFirst = true;
            $isLast  = false;
            $nbReviewers = $rIter->count();
            $i = 0;
            $rIter->rewind();
            while ($rIter->valid()) {
                $isLast = ($i == ($nbReviewers - 1));

                $reviewer = $rIter->current();
                // i+1 to start with 'white'
                $html .= '<tr class="'.html_get_alt_row_color($i+1).'">';

                // Select
                if (!$this->table->isClosed()) {
                    $checkbox = '<input type="checkbox" name="sel_user[]" value="'.$reviewer->getId().'" />';
                } else {
                    $checkbox = '&nbsp;';
                }
                $html .= '<td align="center">'.$checkbox.'</td>';

                // Username
                $html .= '<td>'.$this->hp->purify($uh->getDisplayNameFromUserId($reviewer->getId())).'</td>';

                // Review
                $html .= '<td>'.$this->atf->getReviewStateName($reviewer->getState()).'</td>';

                // Rank
                if (!$this->table->isClosed()) {
                    $rank = $reviewer->getRank();
                    $baseUrl  = '?group_id='.$this->item->getGroupId().'&action=approval_upd_user&id='.$this->item->getId().'&user_id='.$reviewer->getId().'&rank=';

                    $begLink = '';
                    $upLink  = '';
                    if (!$isFirst) {
                        $begIcon = '<img src="'.$docmanIcons->getIcon('move-beginning.png').'" alt="Beginning" />';
                        $begLink = '<a href="'.$baseUrl.'beginning">'.$begIcon.'</a>';
                        $upIcon  = '<img src="'.$docmanIcons->getIcon('move-up.png').'" alt="Up" />';
                        $upLink  = '<a href="'.$baseUrl.'up">'.$upIcon.'</a>';
                    }
                    $endLink  = '';
                    $downLink = '';
                    if (!$isLast) {
                        $endIcon  = '<img src="'.$docmanIcons->getIcon('move-end.png').'" alt="End" />';
                        $endLink  = '<a href="'.$baseUrl.'end">'.$endIcon.'</a>';
                        $downIcon = '<img src="'.$docmanIcons->getIcon('move-down.png').'" alt="Down" />';
                        $downLink = '<a href="'.$baseUrl.'down">'.$downIcon.'</a>';
                    }
                    $rankHtml = $upLink.'&nbsp;'.$downLink.'&nbsp;'.$begLink.'&nbsp;'.$endLink;
                } else {
                    $rankHtml = '&nbsp;';
                }
                $html .= '<td align="center">'.$rankHtml.'</td>';

                $html .= '</tr>';

                $isFirst= false;
                $i++;
                $rIter->next();
            }
            $html .= '</table>';

            // Action with selected reviewers
            if (!$this->table->isClosed()) {
                $html .= '<p>';
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_table_act');
                $vals = array('del',
                              'mail');
                $txts = array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_table_act_rm'),
                              $GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_table_act_mail'));
                $html .= html_build_select_box_from_arrays($vals, $txts, 'sel_user_act', 100, true);
                $html .= '</p>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    function _getAddReviewers()
    {
        $html = '';

        if (($this->table !== null && !$this->table->isClosed())
           || $this->table === null) {
            $atrf = new Docman_ApprovalTableReviewerFactory($this->table, $this->item);
            $ugroups = $atrf->getUgroupsAllowedForTable($this->item->getGroupId());
            $html .= '<div id="docman_approval_table_create_add_reviewers">';
            $html .= '<table>';
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_reviewers_hand').'</td>';
            $html .= '<td><input type="text" name="user_list" value="" id="user_list" class="text_field"/></td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_reviewers_ugroup').'</td>';
            $html .= '<td>'.html_build_multiple_select_box_from_array($ugroups, 'ugroup_list[]', array(), 8, true, '', false, '', false, '', false).'</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
            $js   = "new UserAutoCompleter('user_list', '".util_get_dir_image_theme()."', true);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        } else {
            return $GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_reviewers_oldver');
        }

        return $html;
    }

    /*static*/function displayImportLastTable($onTableUpdate = false)
    {
        $html = '';

        if ($onTableUpdate) {
            $onChange = 'onClick="docman.approvalTableCreate(this.form);"';
        } else {
            $onChange = '';
        }

        if ($onTableUpdate) {
            $html .= '<input type="radio" name="app_table_import" checked="checked" '.$onChange.'/> '.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_keep').'<br />';
        }
        $html .= '<input type="radio" name="app_table_import" value="copy"  '.$onChange.'/> '.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_copy').'<br />';
        $html .= '<input type="radio" name="app_table_import" value="reset" '.$onChange.'/> '.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_reset').'<br />';
        $html .= '<input type="radio" name="app_table_import" value="empty" '.$onChange.'/> '.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_empty');
        $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_help').'</div>';
        return $html;
    }

    function displayUpdateForm()
    {
        $html = '';
        $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="hidden" name="action" value="approval_update" />';

        if ($this->version !== null) {
            $html .= '<input type="hidden" name="version" value="'.$this->version.'" />';
        }

        $html .= $this->_getNewTable();
        $html .= $this->_getGlobalSettings();
        $html .= $this->_getNotificationSettings();
        $html .= $this->_getReviewerTable();

        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_reviewers_title').'</h3>';
        $html .= $this->_getAddReviewers();

        $html .= '<p>';
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_submit').'">';
        $html .= '</p>';
        $html .= '</form>';

        return $html;
    }

    function displayCreateTable()
    {
        $html = '';

        $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';

        $noImport = true;
        // Well all this should be managed by a factory of
        // Docman_View_ItemDetailsSectionApprovalCreate but it's just too
        // complicated with the current implementation of the views to acheive.
        if (is_a($this->atf, 'Docman_ApprovalTableVersionnedFactory')) {
            $lastTable = $this->atf->getLastTableForItem();
            if ($lastTable !== null) {
                $noImport = false;
                $html .= '<input type="hidden" name="action" value="approval_update" />';
                $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_import_title').'</h3>';
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_actions_update_apptable_desc').'<br />';
                $html .= $this->displayImportLastTable();
            }
        }

        if ($noImport) {
            $html .= '<input type="hidden" name="action" value="approval_update" />';
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_reviewers_title').'</h3>';
            $html .= $this->_getAddReviewers();
        }

        $html .= '<p>';
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_create_submit').'">';
        $html .= '</p>';
        $html .= '</form>';

        return $html;
    }


    function getContent($params = [])
    {
        $html = '';

        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if (!$dpm->userCanWrite($user, $this->item->getId())) {
            return $html;
        }

        if (is_a($this->item, 'Docman_Empty')) {
            $html = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_no_table_for_empty');
            return $html;
        }

        $this->initDisplay();

        $request = HTTPRequest::instance();

        // Toolbar
        $html .= $this->getToolbar();

        if ($this->table !== null) {
            // Confirm table deletion
            if ($request->exist('delete')) {
                if ($request->get('delete') == 'confirm') {
                    $html .= $this->displayConfirmDelete();
                    return $html;
                }
            }

            // See notification email
            if ($request->exist('section')) {
                switch ($request->get('section')) {
                    case 'view_notification_email':
                        $html .= $this->displayNotificationEmail();
                        break;
                }
                return $html;
            }

            // Default update panel
            $html .= $this->displayUpdateForm();
        } else {
            $html .= $this->displayCreateTable();
        }

        return $html;
    }
}
