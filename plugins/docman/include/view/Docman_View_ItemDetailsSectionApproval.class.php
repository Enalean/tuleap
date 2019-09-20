<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
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
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Docman\ApprovalTable\HasPotentiallyCorruptedApprovalTable;
use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemDetailsSectionApproval extends Docman_View_ItemDetailsSection
{
    var $table;
    var $atf;
    var $themePath;
    var $version;
    var $notificationManager;

    function __construct($item, $url, $themePath, $notificationManager)
    {
        parent::__construct($item, $url, 'approval', $GLOBALS['Language']->getText('plugin_docman', 'details_approval'));

        $this->themePath = $themePath;
        $this->table = null;
        $this->atf   = null;
        $this->version = null;
        $this->notificationsManager = $notificationManager;
    }

    function initDisplay()
    {
        $request = HTTPRequest::instance();

        // User may request a specific table id
        $vVersion = new Valid_UInt('version');
        $vVersion->required();
        if ($request->valid($vVersion)) {
            $this->version = $request->get('version');
        }

        $this->atf = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item, $this->version);
        $this->table = $this->atf->getTable();
    }

    function _getItemVersionLink($version, $noLink = false)
    {
        $html = '';
        if ($version !== null) {
            $title = '';
            $url   = '';

            $itemType = Docman_ItemFactory::getItemTypeForItem($this->item);
            if ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
               || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                $vFactory = new Docman_VersionFactory();
                $v = $vFactory->getSpecificVersion($this->item, $version);
                if ($v) {
                    $url = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'show', 'id' => $this->item->getId(), 'version_number' => $v->getNumber()]
                    );
                    if ($v->getLabel()) {
                        $title .= $this->hp->purify($v->getLabel()).' - ';
                    }
                }
            } elseif ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $project_id = $this->item->getGroupId();
                $pagename   = urlencode($this->item->getPagename());
                $url        = '/wiki/index.php?group_id='.$project_id.'&pagename='.$pagename.'&version='.$version;
            }
            $title .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_version_link').' '.$version;
            if ($noLink) {
                $html .= $title;
            } else {
                $html .= '<a href="'.$url.'">'.$title.'</a>';
            }
        }
        return $html;
    }

    function getReviewerTable($forceReadOnly = false)
    {
        $html  = '';
        $uh    = UserHelper::instance();
        $rIter = $this->table->getReviewerIterator();
        if ($rIter !== null) {
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_title').'</h3>';

            if (!$this->table->isCustomizable()) {
                $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_oldver').'</p>';
            }

            $user = $this->_getCurrentUser();

            $html .= '<table>';

            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_requester').'</td>';
            $html .= '<td>';
            $html .= $this->hp->purify($uh->getDisplayNameFromUserId($this->table->getOwner()));
            $html .= '</td>';
            $html .= '</tr>';

            // Version
            if ($this->table instanceof \Docman_ApprovalTableVersionned) {
                $html .= '<tr>';
                $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_version').'</td>';
                $html .= '<td>';
                $html .= $this->table->getVersionNumber();
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Notification type
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_type').'</td>';
            $html .= '<td>';
            $html .= $this->atf->getNotificationTypeName($this->table->getNotification());
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_cycle_start_date').'</td>';
            $html .= '<td>';
            $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $this->table->getDate(), true);
            $html .= '</td>';
            $html .= '</tr>';

            if ($this->table->isClosed()) {
                $html .= '<tr>';
                $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_status').'</td>';
                $html .= '<td>';
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_table_'.PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED);
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_owner_comment').'</td>';
            $html .= '<td>';
            $html .= $this->hp->purify($this->table->getDescription(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId());
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</table>';

            $html .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_reviewer'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_state'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_comment'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_date'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_version')));
            $userIsInTable = false;
            $rowColorIdx = 1;
            $rIter->rewind();
            while ($rIter->valid()) {
                $reviewer = $rIter->current();

                $readOnly = true;
                $_trClass = ' class="docman_approval_readonly"';
                if (!$forceReadOnly && ($user->getId() == $reviewer->getId())) {
                    $_trClass = ' class="docman_approval_highlight"';
                    $readOnly = false;
                    $userIsInTable = true;
                }

                $html .= '<tr class="'.html_get_alt_row_color($rowColorIdx++).'">';

                // Name
                $html .= '<td'.$_trClass.'>'.$this->hp->purify($uh->getDisplayNameFromUserId($reviewer->getId())).'</td>';

                // Review
                $_reviewHtml = $this->atf->getReviewStateName($reviewer->getState());
                if (!$readOnly) {
                    $_reviewUrl = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'details', 'id' => $this->item->getId(), 'section' => 'approval', 'review'  => '1']
                    );
                    $_reviewHtml = '<a href="'.$_reviewUrl.'">'.$this->atf->getReviewStateName($reviewer->getState()).'</a>';
                }
                $html .= '<td'.$_trClass.'>'.$_reviewHtml.'</td>';

                // Comment
                $html .= '<td'.$_trClass.'>'.$this->hp->purify($reviewer->getComment(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId()).'</td>';

                // Date
                $date = $reviewer->getReviewDate();
                $_dateHtml = '';
                if ($date) {
                    $_dateHtml = DateHelper::formatForLanguage($GLOBALS['Language'], $date, true);
                }
                $html .= '<td'.$_trClass.'>'.$_dateHtml.'</td>';

                // Version
                $html .= '<td'.$_trClass.'>'.$this->_getItemVersionLink($reviewer->getVersion()).'</td>';

                $html .= '</tr>';
                $rIter->next();
            }

            $html .= '</table>';

            $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_help').'</div>';
        }
        return $html;
    }


    function _getReviewCurrentVersion()
    {
        $version = null;
        $itemFactory = Docman_ItemFactory::instance($this->item->getGroupId());
        $itemType = $itemFactory->getItemTypeForItem($this->item);
        // Get current version for file, embeddedfile and wiki
        switch ($itemType) {
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

    function getReviewForm($user)
    {
        $html = '';
        $uh   = UserHelper::instance();

        // Values
        $itemCurrentVersion = $this->_getReviewCurrentVersion();
        $reviewer = $this->table->getReviewer($user->getId());
        $reviewVersion = $reviewer->getVersion();

        // Output
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_doc_review_title').'</h3>';

        $html .= '<table>';

        // Doc title
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_doc_review_name').'</td>';
        $html .= '<td>';
        $html .=  $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) ;
        if ($itemCurrentVersion == null) {
            $url = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'show', 'id' => $this->item->getId()]
            );
            $html .= ' - ';
            $html .= '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_doc_review_link').'</a>';
        }
        $html .= '</td>';
        $html .= '</tr>';

        // Doc version
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_doc_review_version').'</td>';
        $html .= '<td>';
        if ($itemCurrentVersion !== null) {
            $html .= $this->_getItemVersionLink($itemCurrentVersion);
            if (!$this->atf->userAccessedSinceLastUpdate($user)) {
                // Warn user if he didn't access the last version of document
                $html .= '<span style="margin-left: 2em;">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_wo_access').'</span>';
            }
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_doc_review_version_na');
        }
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_approval_title').'</h3>';
        $html .= '<table>';

        // Requester name
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_requester').'</td>';
        $html .= '<td>';
        $html .= $this->hp->purify($uh->getDisplayNameFromUserId($this->table->getOwner()));
        $html .= '</td>';
        $html .= '</tr>';

        // Notification type
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_notif_type').'</td>';
        $html .= '<td>';
        $html .= $this->atf->getNotificationTypeName($this->table->getNotification());
        $html .= '</td>';
        $html .= '</tr>';

        // Cycle start date
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_cycle_start_date').'</td>';
        $html .= '<td>';
        $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $this->table->getDate(), true);
        $html .= '</td>';
        $html .= '</tr>';

        // Owner comment
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_owner_comment').'</td>';
        $html .= '<td>';
        $html .= $this->hp->purify($this->table->getDescription(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId());
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_title').'</h3>';

        $html .= '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_help').'</div>';

        $html .= '<form name="docman_approval_review" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="'.$this->item->getGroupId().'" />';
        $html .= '<input type="hidden" name="id" value="'.$this->item->getId().'" />';
        $html .= '<input type="hidden" name="action" value="approval_user_commit" />';
        if ($itemCurrentVersion !== null) {
            // Add version here because someone can submit a new version while
            // current user is reviewing.
            $html .= '<input type="hidden" name="version" value="'.$itemCurrentVersion.'" />';
        }

        $html .= '<table>';

        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_table').'</td>';
        $url   = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            ['default_url' => $this->url],
            ['action' => 'details', 'section' => 'approval', 'id' => $this->item->getId()]
        );
        $html .= '<td>';
        $html .= '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_table_link').'</a>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_review').'</td>';
        $vals = array(PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
                      PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
                      PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED,
                      PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED,
                      PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED);
        $txts = array($GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET),
                      $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED),
                      $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED),
                      $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED),
                      $GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED));
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'state', $reviewer->getState(), false);
        $html .= '</td>';
        $html .= '</tr>';

        // If reviewer already approved or reject, display date
        if ($reviewer->getReviewDate()) {
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_date').'</td>';
            $html .= '<td>';
            $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $reviewer->getReviewDate(), true);
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Review version
        if ($reviewVersion) {
            $html .= '<tr>';
            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_version').'</td>';
            $html .= '<td>';
            $html .= $this->_getItemVersionLink($reviewVersion, true);
            if ($reviewVersion != $itemCurrentVersion) {
                $html .= '<span style="margin-left: 2em;">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_version_not_upd').'</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Comment
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_comment').'</td>';
        $html .= '<td>';
        $html .= '<textarea name="comment">'.$this->hp->purify($reviewer->getComment()).'</textarea>';
        $html .= '</td>';
        $html .= '</tr>';

        // Notification
        $notifChecked  = !$user->isAnonymous() && $this->notificationsManager->userExists($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $html .= '<tr>';
        $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_notif').'</td>';
        $html .= '<td>';
        $html .= '<input type="checkbox" name="monitor" value="1"'.$notifChecked.' />';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_sendemail');
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2">';
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_review_submit').'">';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '</form>';

        return $html;
    }

    function getTableHistory()
    {
        $html = '';
        $uh   = UserHelper::instance();
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_history_title').'</h3>';
            $html .= html_build_list_table_top(array($GLOBALS['Language']->getText('plugin_docman', 'details_approval_history_table_version'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_history_table_owner'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_history_table_status'),
                                                     $GLOBALS['Language']->getText('plugin_docman', 'details_approval_history_table_date'),
                                                     ));
            $allTables = $this->atf->getAllApprovalTable();
            $rowColorIdx = 1;
            foreach ($allTables as $table) {
                $html .= '<tr class="'.html_get_alt_row_color($rowColorIdx++).'">';
                if ($this->table->getVersionNumber() != $table->getVersionNumber()) {
                    $url = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        [
                            'action'  => 'details',
                            'section' => 'approval',
                            'id'      => $this->item->getId(),
                            'version' => $table->getVersionNumber()
                        ]
                    );
                    $href = '<a href="'.$url.'">'.$table->getVersionNumber().'</a>';
                } else {
                    $href = $table->getVersionNumber();
                }
                $html .= '<td>'.$href.'</td>';
                $html .= '<td>'.$this->hp->purify($uh->getDisplayNameFromUserId($table->getOwner())).'</td>';
                $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman', 'approval_review_state_'.$table->getApprovalState()).'</td>';
                $html .= '<td>'.DateHelper::formatForLanguage($GLOBALS['Language'], $table->getDate()) .'</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    function getToolbar()
    {
        $html = '';
        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if ($dpm->userCanWrite($user, $this->item->getId())) {
            $url = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'approval_create', 'id' => $this->item->getId()]
            );
            $adminLink = '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_admin').'</a>';
            $html = '<strong>'.$adminLink.'</strong><br />';
        }
        return $html;
    }

    function getContent($params = [])
    {
        $html = '';

        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if (!$dpm->userCanRead($user, $this->item->getId())) {
            return $html;
        }

        if (is_a($this->item, 'Docman_Empty')) {
            $html = $GLOBALS['Language']->getText('plugin_docman', 'details_approval_no_table_for_empty');
            return $html;
        }

        $this->initDisplay();

        $request = HTTPRequest::instance();

        // Show toolbar
        $html .= $this->getToolbar();

        if ($this->item->accept(
            new HasPotentiallyCorruptedApprovalTable(new Docman_ApprovalTableFileDao(), new Docman_LinkVersionFactory()),
            ['approval_table' => $this->table, 'version_number' => $this->version]
        )
        ) {
            $html .= '<div class="feedback_warning">' .
                dgettext(
                    'tuleap-docman',
                    'This approval table has been detected has potentially corrupted, please reach out to the site administrators if you notice inconsistencies.'
                )
                . '</div>';
        }

        // Show Content
        if ($this->table === null) {
            $html .= '<p>';
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_no_table');
            if ($dpm->userCanWrite($user, $this->item->getId())) {
                $url = DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'approval_create', 'id' => $this->item->getId()]
                );
                $adminLink = '<a href="'.$url.'">'.$GLOBALS['Language']->getText('plugin_docman', 'details_approval_no_table_create').'</a>';
                $html .= ' <strong>'.$adminLink.'</strong><br />';
            }
            $html .= '</p>';
        } elseif ($this->table->isDisabled()) {
            $html .= '<p>';
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'details_approval_not_available');
            $html .= '</p>';
            $html .= $this->getTableHistory();
        } else {
            // '&user_id=XX' was used in CX_3_4 to identify users. Now it's '&review=1'
            // We should keep this part of the test until CX_3_8.
            if (($request->exist('review') || $request->exist('user_id'))
                && $this->table->isReviewer($user->getId())
                && $this->table->isEnabled()) {
                $html .= $this->getReviewForm($user);
            } else {
                $forceReadOnly = false;
                if ($this->table->isClosed()) {
                    $forceReadOnly = true;
                }
                $html .= $this->getReviewerTable($forceReadOnly);
                $html .= $this->getTableHistory();
            }
        }

        return $html;
    }

    function &_getDocmanIcons()
    {
        $icons = new Docman_Icons($this->themePath.'/images/ic/');
        return $icons;
    }

    function &_getUserManager()
    {
        $um = UserManager::instance();
        return $um;
    }

    function &_getCurrentUser()
    {
        $um   = $this->_getUserManager();
        $user = $um->getCurrentUser();
        return $user;
    }

    function &_getPermissionsManager()
    {
        $dpm = Docman_PermissionsManager::instance($this->item->getGroupId());
        return $dpm;
    }

    function visitFolder($item, $params = array())
    {
        return '';
    }

    function visitWiki($item, $params = array())
    {
        return '';
    }

    function visitLink($item, $params = array())
    {
        return '';
    }

    function visitFile($item, $params = array())
    {
        return '';
    }

    function visitEmbeddedFile($item, $params = array())
    {
        return '';
    }

    function visitEmpty($item, $params = array())
    {
        return '';
    }
}
