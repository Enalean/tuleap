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

    public function __construct($item, $url, $themePath)
    {
        parent::__construct($item, $url, $themePath, null);
    }

    public function displayConfirmDelete()
    {
        $html = '';
        $html .= '<form action="' . $this->url . '" method="POST" class="docman_confirm_delete">';
        $html .= dgettext('tuleap-docman', '<h3>Confirm deletion of approval table</h3><p>You are going to delete an approval table. Please note that all the data (reviewer list, current reviews, settings) will be deleted.</p><p>Are you sure that you want to continue?</p>');
        $html .= '<div class="docman_confirm_delete_buttons">';
        $html .= '<input type="hidden" name="action" value="approval_delete" />';
        $html .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        if ($this->version !== null) {
            $html .= '<input type="hidden" name="version" value="' . $this->version . '" />';
        }
        $html .= '<input type="submit" name="cancel" value="' . dgettext('tuleap-docman', 'No, I do not want to delete it') . '" />';
        $html .= '<input type="submit" name="confirm" value="' . dgettext('tuleap-docman', 'Yes, I am sure!') . '" />';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }

    public function displayNotificationEmail()
    {
        $html = '';
        $html .= '<h3>' . dgettext('tuleap-docman', 'Notification email') . '</h3>';

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
        $html .= dgettext('tuleap-docman', 'Subject:') . ' ' . $atsm->getNotificationSubject() . "\n";
        $html .= '<p class="docman_approval_email">';

        if (ProjectManager::instance()->getProject($this->item->getGroupId())->getTruncatedEmailsUsage()) {
            $html .= dgettext('tuleap-docman', 'truncated email');
        } else {
            $html .= htmlentities(quoted_printable_decode($atsm->getNotificationBodyText()), ENT_COMPAT, 'UTF-8');
        }
        $html .= '</p>';
        $backurl = $this->url . '&action=approval_create&id=' . $this->item->getId();
        $html .= '<a href="' . $backurl . '">' . dgettext('tuleap-docman', 'Back to the approval settings.') . '</a>';
        return $html;
    }

    public function _getNewTable()
    {
        $html = '';
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $lastDocumentVersion = $this->atf->getLastDocumentVersionNumber();
            if ($this->table->getVersionNumber() < $lastDocumentVersion) {
                $html .= '<h3>' . dgettext('tuleap-docman', 'Create a new approval table') . '</h3>';
                $html .= '<p>' . sprintf(dgettext('tuleap-docman', '<big><strong>Warning:</strong></big> this table is linked to an old version of the document (version %1$s). The last document version is %2$s, you can either:'), $this->table->getVersionNumber(), $lastDocumentVersion) . '</p>';
                $html .= $this->displayImportLastTable(true);
            }
        }
        return $html;
    }

    public function _getGlobalSettings()
    {
        $html = '';

        $html .= '<h3>' . dgettext('tuleap-docman', 'Approval table global settings') . '</h3>';
        $html .= '<div id="docman_approval_table_create_settings">';
        $html .= '<table>';

        // Version
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Attached to document version:') . '</td>';
            $html .= '<td>';
            $html .= $this->table->getVersionNumber();
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Owner
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Approval requester:') . '</td>';
        $html .= '<td>';
        $html .= '<input type="text" class="text_field" name="table_owner" value="' . user_getname($this->table->getOwner()) . '" />';
        $html .= '</td>';
        $html .= '</tr>';

        // Status
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Table status:') . '</td>';
        $vals = array(0 => PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED,
                      1 => PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED);
        $txts = array(0 => dgettext('tuleap-docman', 'Closed'),
                      1 => dgettext('tuleap-docman', 'Deleted'));
        if ($this->table->isCustomizable()) {
            $vals[2] = PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED;
            $vals[3] = PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED;
            $txts[2] = dgettext('tuleap-docman', 'Disabled');
            $txts[3] = dgettext('tuleap-docman', 'Available');
        }
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'status', $this->table->getStatus(), false);
        $html .= '</td>';
        $html .= '</tr>';

        // Description
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Comment:') . '</td>';
        $html .= '<td>';
        $html .= '<textarea name="description">' . $this->hp->purify($this->table->getDescription()) . '</textarea>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function _getNotificationSettings()
    {
        $html = '';

        $html .= '<h3>' . dgettext('tuleap-docman', 'Notification') . '</h3>';
        $html .= '<div id="docman_approval_table_create_notification">';
        if (!$this->table->isClosed()) {
            $html .= '<div class="docman_help">' . sprintf(dgettext('tuleap-docman', '%1$s can notify approval members in two ways:<ul><li><strong>All at once:</strong> notify all reviewers who did not commit themselves yet.</li><li><strong>Sequential:</strong> notify reviewers (who did not commit themselves) one after another. If someone reject the document, the sequence stops.</li></ul>After an approver is notified by the approval table, it is informed of any later modification done on the document.'), $GLOBALS['sys_name']) . '</div>';
        }
        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Notification Type:') . '</td>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE,
                      PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL);
        $txts = array(dgettext('tuleap-docman', 'Disabled'),
                      dgettext('tuleap-docman', 'All at once'),
                      dgettext('tuleap-docman', 'Sequential'));
        $html .= '<td>';
        if (!$this->table->isClosed()) {
            $html .= html_build_select_box_from_arrays($vals, $txts, 'notification', $this->table->getNotification(), false);
        } else {
            switch ($this->table->getNotification()) {
                case PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED:
                    $html .= dgettext('tuleap-docman', 'Disabled');
                    break;
                case PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED:
                    $html .= dgettext('tuleap-docman', 'All at once');
                    break;
                case PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED:
                    $html .= dgettext('tuleap-docman', 'Sequential');
                    break;
            }
        }
        $html .= '</td>';
        $html .= '</tr>';

        if (!$this->table->isClosed()) {
            if ($this->table->getNotification() != PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED) {
                if ($this->table->isEnabled()) {
                    $html .= '<tr>';
                    $html .= '<td>' . dgettext('tuleap-docman', 'Relaunch notification:') . '</td>';
                    $vals = array('no',
                                  'yes');
                    $txts = array($GLOBALS['Language']->getText('global', 'no'),
                                  $GLOBALS['Language']->getText('global', 'yes'));
                    $html .= '<td>' . html_build_select_box_from_arrays($vals, $txts, 'resend_notif', 'no', false) . '</td>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td>' . dgettext('tuleap-docman', 'Notification email') . '</td>';
                $html .= '<td>';
                $html .= '<a href="' . $this->url . '&action=approval_create&id=' . $this->item->getId() . '&section=view_notification_email">' . dgettext('tuleap-docman', 'See the notification email') . '</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        $html .= $this->_displayNotificationOccurence();
        $html .= '</table>';
        $html .= '</div>';
        return $html;
    }

    public function _displayNotificationOccurence()
    {
        $html = '<tr>';
        $html .= '<td>';
        $html .= '<h4>' . dgettext('tuleap-docman', 'Mail reminder occurence') . '</h4>';
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
        $html .= '<span id="approval_table_reminder" ></span><input id="approval_table_reminder_checkbox" type="checkbox" name="reminder" ' . $checked . ' /></span>';
        $html .= ' ';
        $html .= dgettext('tuleap-docman', 'Send a mail reminder to approver(s)');
        $html .= ' </td>';
        $html .= ' </tr>';
        $html .= ' <tr>';
        $html .= '<td>';
        $html .= '<span id="approval_table_occurence_form" > ' . dgettext('tuleap-docman', 'Every');
        $html .= '<input size="2" name="occurence" value="' . $occurence . '" /> ';
        $html .= html_build_select_box_from_arrays(array(1, 7), array(dgettext('tuleap-docman', 'Days'), dgettext('tuleap-docman', 'Weeks')), 'period', null, false);
        $html .= '</span>';
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    public function _getReviewerTable()
    {
        $html  = '';
        $uh    = UserHelper::instance();
        $html .= '<h3>' . dgettext('tuleap-docman', 'Approval table') . '</h3>';
        $html .= '<div id="docman_approval_table_create_table">';
        if (!$this->table->isClosed()) {
            $html .= '<div class="docman_help">' . dgettext('tuleap-docman', 'Following table aims to organize the approval cycle:<ul><li>With <strong>Select</strong> you choose the reviewers on which you want to apply <strong>Actions</strong> (see below for available actions)</li><li>With <strong>Rank</strong> you select the order the emails will be sent to notify people (only in <em>Sequential</em> notification)</li><li>With <strong>Actions</strong> you can (with selected reviewers) either:<ul><li><strong>Remove from table</strong>. No history kept.</li><li>or <strong>Force notification</strong> to send the notification email regardless of their review state of the notififcation type.</li></ul></li></ul>') . '</div>';
        }
        $rIter = $this->table->getReviewerIterator();
        if ($rIter !== null) {
            $docmanIcons = $this->_getDocmanIcons();

            $html .= html_build_list_table_top(
                array(dgettext('tuleap-docman', 'Select'),
                                                     dgettext('tuleap-docman', 'Name'),
                                                     dgettext('tuleap-docman', 'Review'),
                                                     dgettext('tuleap-docman', 'Rank')),
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
                $html .= '<tr class="' . html_get_alt_row_color($i + 1) . '">';

                // Select
                if (!$this->table->isClosed()) {
                    $checkbox = '<input type="checkbox" name="sel_user[]" value="' . $reviewer->getId() . '" />';
                } else {
                    $checkbox = '&nbsp;';
                }
                $html .= '<td align="center">' . $checkbox . '</td>';

                // Username
                $html .= '<td>' . $this->hp->purify($uh->getDisplayNameFromUserId($reviewer->getId())) . '</td>';

                // Review
                $html .= '<td>' . $this->atf->getReviewStateName($reviewer->getState()) . '</td>';

                // Rank
                if (!$this->table->isClosed()) {
                    $rank = $reviewer->getRank();
                    $baseUrl  = '?group_id=' . $this->item->getGroupId() . '&action=approval_upd_user&id=' . $this->item->getId() . '&user_id=' . $reviewer->getId() . '&rank=';

                    $begLink = '';
                    $upLink  = '';
                    if (!$isFirst) {
                        $begIcon = '<img src="' . $docmanIcons->getIcon('move-beginning.png') . '" alt="Beginning" />';
                        $begLink = '<a href="' . $baseUrl . 'beginning">' . $begIcon . '</a>';
                        $upIcon  = '<img src="' . $docmanIcons->getIcon('move-up.png') . '" alt="Up" />';
                        $upLink  = '<a href="' . $baseUrl . 'up">' . $upIcon . '</a>';
                    }
                    $endLink  = '';
                    $downLink = '';
                    if (!$isLast) {
                        $endIcon  = '<img src="' . $docmanIcons->getIcon('move-end.png') . '" alt="End" />';
                        $endLink  = '<a href="' . $baseUrl . 'end">' . $endIcon . '</a>';
                        $downIcon = '<img src="' . $docmanIcons->getIcon('move-down.png') . '" alt="Down" />';
                        $downLink = '<a href="' . $baseUrl . 'down">' . $downIcon . '</a>';
                    }
                    $rankHtml = $upLink . '&nbsp;' . $downLink . '&nbsp;' . $begLink . '&nbsp;' . $endLink;
                } else {
                    $rankHtml = '&nbsp;';
                }
                $html .= '<td align="center">' . $rankHtml . '</td>';

                $html .= '</tr>';

                $isFirst = false;
                $i++;
                $rIter->next();
            }
            $html .= '</table>';

            // Action with selected reviewers
            if (!$this->table->isClosed()) {
                $html .= '<p>';
                $html .= dgettext('tuleap-docman', '<strong>Actions</strong> with selected reviewers:');
                $vals = array('del',
                              'mail');
                $txts = array(dgettext('tuleap-docman', 'Remove from table'),
                              dgettext('tuleap-docman', 'Force notification'));
                $html .= html_build_select_box_from_arrays($vals, $txts, 'sel_user_act', 100, true);
                $html .= '</p>';
            }
        }
        $html .= '</div>';
        return $html;
    }

    public function _getAddReviewers()
    {
        $html = '';

        if (($this->table !== null && !$this->table->isClosed())
           || $this->table === null) {
            $atrf = new Docman_ApprovalTableReviewerFactory($this->table, $this->item);
            $ugroups = $atrf->getUgroupsAllowedForTable($this->item->getGroupId());
            $html .= '<div id="docman_approval_table_create_add_reviewers">';
            $html .= '<table>';
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Enter list by hand (separate by \',\'):') . '</td>';
            $html .= '<td><input type="text" name="user_list" value="" id="user_list" class="text_field"/></td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Append the members to the table:') . '</td>';
            $html .= '<td>' . html_build_multiple_select_box_from_array($ugroups, 'ugroup_list[]', array(), 8, true, '', false, '', false, '', false) . '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
            $js   = "new UserAutoCompleter('user_list', '" . util_get_dir_image_theme() . "', true);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        } else {
            return dgettext('tuleap-docman', 'You cannot add people to an old approval table.');
        }

        return $html;
    }

    /*static*/public function displayImportLastTable($onTableUpdate = false)
    {
        $html = '';

        if ($onTableUpdate) {
            $onChange = 'onClick="docman.approvalTableCreate(this.form);"';
        } else {
            $onChange = '';
        }

        if ($onTableUpdate) {
            $html .= '<input type="radio" name="app_table_import" checked="checked" ' . $onChange . '/> ' . dgettext('tuleap-docman', 'Keep the current table.') . '<br />';
        }
        $html .= '<input type="radio" name="app_table_import" value="copy"  ' . $onChange . '/> ' . dgettext('tuleap-docman', 'Copy the previous approval table (e.g. for small / typo updates).') . '<br />';
        $html .= '<input type="radio" name="app_table_import" value="reset" ' . $onChange . '/> ' . dgettext('tuleap-docman', 'Reset the approval cycle (e.g. for major rewrite).') . '<br />';
        $html .= '<input type="radio" name="app_table_import" value="empty" ' . $onChange . '/> ' . dgettext('tuleap-docman', 'Create an new empty table.');
        $html .= '<div class="docman_help">' . dgettext('tuleap-docman', '<p>If you choose to:<ul><li><strong>Copy the previous approval table:</strong> A new approval table will be created from the previous one. Keeps reviewers, comments and commitments.</li><li><strong>Reset the approval cycle:</strong> Only the table structure will be kept (description, notification type, reviewer list.). Actually, the new table will be identical to the previous one, but without reviewer comments and commitements.</li><li><strong>Create an new empty table:</strong> Start over with a completly new approval table.</li></ul>In all cases, There is <strong>no automatic notification</strong> of the reviewers.</p>') . '</div>';
        return $html;
    }

    public function displayUpdateForm()
    {
        $html = '';
        $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="' . $this->item->getGroupId() . '" />';
        $html .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        $html .= '<input type="hidden" name="action" value="approval_update" />';

        if ($this->version !== null) {
            $html .= '<input type="hidden" name="version" value="' . $this->version . '" />';
        }

        $html .= $this->_getNewTable();
        $html .= $this->_getGlobalSettings();
        $html .= $this->_getNotificationSettings();
        $html .= $this->_getReviewerTable();

        $html .= '<h3>' . dgettext('tuleap-docman', 'Add reviewers') . '</h3>';
        $html .= $this->_getAddReviewers();

        $html .= '<p>';
        $html .= '<input type="submit" value="' . dgettext('tuleap-docman', 'Update') . '">';
        $html .= '</p>';
        $html .= '</form>';

        return $html;
    }

    public function displayCreateTable()
    {
        $html = '';

        $html .= '<form name="docman_approval_settings" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="' . $this->item->getGroupId() . '" />';
        $html .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';

        $noImport = true;
        // Well all this should be managed by a factory of
        // Docman_View_ItemDetailsSectionApprovalCreate but it's just too
        // complicated with the current implementation of the views to acheive.
        if (is_a($this->atf, 'Docman_ApprovalTableVersionnedFactory')) {
            $lastTable = $this->atf->getLastTableForItem();
            if ($lastTable !== null) {
                $noImport = false;
                $html .= '<input type="hidden" name="action" value="approval_update" />';
                $html .= '<h3>' . dgettext('tuleap-docman', 'Import an old approval table') . '</h3>';
                $html .= dgettext('tuleap-docman', 'There is an approval table on the previous version of the document. You can either:') . '<br />';
                $html .= $this->displayImportLastTable();
            }
        }

        if ($noImport) {
            $html .= '<input type="hidden" name="action" value="approval_update" />';
            $html .= '<h3>' . dgettext('tuleap-docman', 'Add reviewers') . '</h3>';
            $html .= $this->_getAddReviewers();
        }

        $html .= '<p>';
        $html .= '<input type="submit" value="' . dgettext('tuleap-docman', 'Update') . '">';
        $html .= '</p>';
        $html .= '</form>';

        return $html;
    }


    public function getContent($params = [])
    {
        $html = '';

        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if (!$dpm->userCanWrite($user, $this->item->getId())) {
            return $html;
        }

        if (is_a($this->item, 'Docman_Empty')) {
            $html = dgettext('tuleap-docman', 'This is not possible to create approval table for Empty documents.');
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
