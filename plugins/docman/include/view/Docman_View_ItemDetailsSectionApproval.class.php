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

use Tuleap\Date\DateHelper;
use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemDetailsSectionApproval extends Docman_View_ItemDetailsSection
{
    public $table;
    public $atf;
    public $themePath;
    public $version;
    public $notificationManager;

    public function __construct($item, $url, $themePath, $notificationManager)
    {
        parent::__construct($item, $url, 'approval', dgettext('tuleap-docman', 'Approval Table'));

        $this->themePath            = $themePath;
        $this->table                = null;
        $this->atf                  = null;
        $this->version              = null;
        $this->notificationsManager = $notificationManager;
    }

    public function initDisplay()
    {
        $request = HTTPRequest::instance();

        // User may request a specific table id
        $vVersion = new Valid_UInt('version');
        $vVersion->required();
        if ($request->valid($vVersion)) {
            $this->version = $request->get('version');
        }

        $this->atf   = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item, $this->version);
        $this->table = $this->atf->getTable();
    }

    public function _getItemVersionLink($version, $noLink = false)
    {
        $html = '';
        if ($version !== null) {
            $title = '';
            $url   = '';

            $itemType = (new Docman_ItemFactory())->getItemTypeForItem($this->item);
            if (
                $itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
                || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE
            ) {
                $vFactory = new Docman_VersionFactory();
                $v        = $vFactory->getSpecificVersion($this->item, $version);
                if ($v) {
                    $url = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'show', 'id' => $this->item->getId(), 'version_number' => $v->getNumber()]
                    );
                    if ($v->getLabel()) {
                        $title .= $this->hp->purify($v->getLabel()) . ' - ';
                    }
                }
            } elseif ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $project_id = $this->item->getGroupId();
                $pagename   = urlencode($this->item->getPagename());
                $url        = '/wiki/index.php?group_id=' . $project_id . '&pagename=' . $pagename . '&version=' . $version;
            }
            $title .= dgettext('tuleap-docman', 'version') . ' ' . $version;
            if ($noLink) {
                $html .= $title;
            } else {
                $html .= '<a href="' . $url . '">' . $title . '</a>';
            }
        }
        return $html;
    }

    public function getReviewerTable($forceReadOnly = false)
    {
        $html  = '';
        $uh    = UserHelper::instance();
        $rIter = $this->table->getReviewerIterator();
        if ($rIter !== null) {
            $html .= '<h3>' . dgettext('tuleap-docman', 'Approval table') . '</h3>';

            if (! $this->table->isCustomizable()) {
                $html .= '<p>' . dgettext('tuleap-docman', 'This table is linked to an old version of the document.') . '</p>';
            }

            $user = $this->_getCurrentUser();

            $html .= '<table>';

            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Approval requester:') . '</td>';
            $html .= '<td>';
            $html .= $this->hp->purify($uh->getDisplayNameFromUserId($this->table->getOwner()));
            $html .= '</td>';
            $html .= '</tr>';

            // Version
            if ($this->table instanceof \Docman_ApprovalTableVersionned) {
                $html .= '<tr>';
                $html .= '<td>' . dgettext('tuleap-docman', 'Attached to document version:') . '</td>';
                $html .= '<td>';
                $html .= $this->table->getVersionNumber();
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Notification type
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Notification Type:') . '</td>';
            $html .= '<td>';
            $html .= $this->atf->getNotificationTypeName($this->table->getNotification());
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Approval cycle start date:') . '</td>';
            $html .= '<td>';
            $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $this->table->getDate(), true);
            $html .= '</td>';
            $html .= '</tr>';

            if ($this->table->isClosed()) {
                $html .= '<tr>';
                $html .= '<td>' . dgettext('tuleap-docman', 'Table status:') . '</td>';
                $html .= '<td>';
                $html .= dgettext('tuleap-docman', 'Closed');
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Requester comment:') . '</td>';
            $html .= '<td>';
            $html .= $this->hp->purify($this->table->getDescription(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId());
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</table>';

            $html         .= html_build_list_table_top([dgettext('tuleap-docman', 'Name'),
                dgettext('tuleap-docman', 'Review'),
                dgettext('tuleap-docman', 'Comment'),
                dgettext('tuleap-docman', 'Date'),
                dgettext('tuleap-docman', 'Version'),
            ]);
            $userIsInTable = false;
            $rowColorIdx   = 1;
            $rIter->rewind();
            while ($rIter->valid()) {
                $reviewer = $rIter->current();

                $readOnly = true;
                $_trClass = ' class="docman_approval_readonly"';
                if (! $forceReadOnly && ($user->getId() == $reviewer->getId())) {
                    $_trClass      = ' class="docman_approval_highlight"';
                    $readOnly      = false;
                    $userIsInTable = true;
                }

                $html .= '<tr class="' . html_get_alt_row_color($rowColorIdx++) . '">';

                // Name
                $html .= '<td' . $_trClass . '>' . $this->hp->purify($uh->getDisplayNameFromUserId($reviewer->getId())) . '</td>';

                // Review
                $_reviewHtml = $this->atf->getReviewStateName($reviewer->getState());
                if (! $readOnly) {
                    $_reviewUrl  = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'details', 'id' => $this->item->getId(), 'section' => 'approval', 'review'  => '1']
                    );
                    $_reviewHtml = '<a href="' . $_reviewUrl . '">' . $this->atf->getReviewStateName($reviewer->getState()) . '</a>';
                }
                $html .= '<td' . $_trClass . '>' . $_reviewHtml . '</td>';

                // Comment
                $html .= '<td' . $_trClass . '>' . $this->hp->purify($reviewer->getComment(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId()) . '</td>';

                // Date
                $date      = $reviewer->getReviewDate();
                $_dateHtml = '';
                if ($date) {
                    $_dateHtml = DateHelper::formatForLanguage($GLOBALS['Language'], $date, true);
                }
                $html .= '<td' . $_trClass . '>' . $_dateHtml . '</td>';

                // Version
                $html .= '<td' . $_trClass . '>' . $this->_getItemVersionLink($reviewer->getVersion()) . '</td>';

                $html .= '</tr>';
                $rIter->next();
            }

            $html .= '</table>';

            $html .= '<div class="docman_help">' . dgettext('tuleap-docman', 'Possible commitment are:<ul><li><strong>Not yet:</strong> Status quo. You can add comment but nobody will be notifed.</li><li><strong>Approve:</strong> Sends an email to the approval requester and notify next reviewer in sequence if any.</li><li><strong>Reject:</strong> Sends an email to the approval requester and stops the approval sequence if any.</li><li><strong>Comment only:</strong> Sends an email to the approval requester to inform there are comments. In <em>Sequential</em> notification type, the notification workflow is frozen.</li><li><strong>Will not review:</strong> Sends an email to the approval requester and notify next reviewer in sequence if any.</li></ul>') . '</div>';
        }
        return $html;
    }

    public function _getReviewCurrentVersion()
    {
        $version     = null;
        $itemFactory = Docman_ItemFactory::instance($this->item->getGroupId());
        $itemType    = $itemFactory->getItemTypeForItem($this->item);
        // Get current version for file, embeddedfile and wiki
        switch ($itemType) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $currentVersion = $this->item->getCurrentVersion();
                $version        = $currentVersion->getNumber();
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $version = $itemFactory->getCurrentWikiVersion($this->item);
                break;
        }
        return $version;
    }

    public function getReviewForm($user)
    {
        $html = '';
        $uh   = UserHelper::instance();

        // Values
        $itemCurrentVersion = $this->_getReviewCurrentVersion();
        $reviewer           = $this->table->getReviewer($user->getId());
        $reviewVersion      = $reviewer->getVersion();

        // Output
        $html .= '<h3>' . dgettext('tuleap-docman', 'Document under review') . '</h3>';

        $html .= '<table>';

        // Doc title
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Document name:') . '</td>';
        $html .= '<td>';
        $html .=  $this->hp->purify($this->item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML);
        if ($itemCurrentVersion == null) {
            $url   = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'show', 'id' => $this->item->getId()]
            );
            $html .= ' - ';
            $html .= '<a href="' . $url . '">' . dgettext('tuleap-docman', 'Click to open the document') . '</a>';
        }
        $html .= '</td>';
        $html .= '</tr>';

        // Doc version
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Document version:') . '</td>';
        $html .= '<td>';
        if ($itemCurrentVersion !== null) {
            $html .= $this->_getItemVersionLink($itemCurrentVersion);
            if (! $this->atf->userAccessedSinceLastUpdate($user)) {
                // Warn user if he didn't access the last version of document
                $html .= '<span style="margin-left: 2em;">' . dgettext('tuleap-docman', 'You <strong>did not read this version</strong> of the document.') . '</span>';
            }
        } else {
            $html .= dgettext('tuleap-docman', 'Not applicable');
        }
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '<h3>' . dgettext('tuleap-docman', 'Approval cycle details') . '</h3>';
        $html .= '<table>';

        // Requester name
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Approval requester:') . '</td>';
        $html .= '<td>';
        $html .= $this->hp->purify($uh->getDisplayNameFromUserId($this->table->getOwner()));
        $html .= '</td>';
        $html .= '</tr>';

        // Notification type
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Notification Type:') . '</td>';
        $html .= '<td>';
        $html .= $this->atf->getNotificationTypeName($this->table->getNotification());
        $html .= '</td>';
        $html .= '</tr>';

        // Cycle start date
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Approval cycle start date:') . '</td>';
        $html .= '<td>';
        $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $this->table->getDate(), true);
        $html .= '</td>';
        $html .= '</tr>';

        // Owner comment
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Requester comment:') . '</td>';
        $html .= '<td>';
        $html .= $this->hp->purify($this->table->getDescription(), CODENDI_PURIFIER_BASIC, $this->item->getGroupId());
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '<h3>' . dgettext('tuleap-docman', 'Review') . '</h3>';

        $html .= '<div class="docman_help">' . dgettext('tuleap-docman', 'Possible commitment are:<ul><li><strong>Not yet:</strong> Status quo. You can add comment but nobody will be notifed.</li><li><strong>Approve:</strong> Sends an email to the approval requester and notify next reviewer in sequence if any.</li><li><strong>Reject:</strong> Sends an email to the approval requester and stops the approval sequence if any.</li><li><strong>Comment only:</strong> Sends an email to the approval requester to inform there are comments. In <em>Sequential</em> notification type, the notification workflow is frozen.</li><li><strong>Will not review:</strong> Sends an email to the approval requester and notify next reviewer in sequence if any.</li></ul>') . '</div>';

        $html .= '<form name="docman_approval_review" method="post" action="?" class="docman_form">';
        $html .= '<input type="hidden" name="group_id" value="' . $this->item->getGroupId() . '" />';
        $html .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        $html .= '<input type="hidden" name="action" value="approval_user_commit" />';
        if ($itemCurrentVersion !== null) {
            // Add version here because someone can submit a new version while
            // current user is reviewing.
            $html .= '<input type="hidden" name="version" value="' . $itemCurrentVersion . '" />';
        }

        $html .= '<table>';

        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Approval table:') . '</td>';
        $url   = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            ['default_url' => $this->url],
            ['action' => 'details', 'section' => 'approval', 'id' => $this->item->getId()]
        );
        $html .= '<td>';
        $html .= '<a href="' . $url . '">' . dgettext('tuleap-docman', 'Click to see the approval table') . '</a>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Review:') . '</td>';
        $vals  = [PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET,
            PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED,
            PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED,
            PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED,
            PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED,
        ];
        $txts  = [dgettext('tuleap-docman', 'Not Yet'),
            dgettext('tuleap-docman', 'Approved'),
            dgettext('tuleap-docman', 'Rejected'),
            dgettext('tuleap-docman', 'Comment only'),
            dgettext('tuleap-docman', 'Will not review'),
        ];
        $html .= '<td>';
        $html .= html_build_select_box_from_arrays($vals, $txts, 'state', $reviewer->getState(), false);
        $html .= '</td>';
        $html .= '</tr>';

        // If reviewer already approved or reject, display date
        if ($reviewer->getReviewDate()) {
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Review date:') . '</td>';
            $html .= '<td>';
            $html .= DateHelper::formatForLanguage($GLOBALS['Language'], $reviewer->getReviewDate(), true);
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Review version
        if ($reviewVersion) {
            $html .= '<tr>';
            $html .= '<td>' . dgettext('tuleap-docman', 'Version reviewed:') . '</td>';
            $html .= '<td>';
            $html .= $this->_getItemVersionLink($reviewVersion, true);
            if ($reviewVersion != $itemCurrentVersion) {
                $html .= '<span style="margin-left: 2em;">' . dgettext('tuleap-docman', 'You already <strong>reviewed an old version</strong> of the document.') . '</span>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        // Comment
        $html .= '<tr>';
        $html .= '<td>' . dgettext('tuleap-docman', 'Add a comment:') . '</td>';
        $html .= '<td>';
        $html .= '<textarea name="comment">' . $this->hp->purify($reviewer->getComment()) . '</textarea>';
        $html .= '</td>';
        $html .= '</tr>';

        // Notification
        $notifChecked = ! $user->isAnonymous() && $this->notificationsManager->userExists($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $html        .= '<tr>';
        $html        .= '<td>' . dgettext('tuleap-docman', 'Notification:') . '</td>';
        $html        .= '<td>';
        $html        .= '<input type="checkbox" name="monitor" value="1"' . $notifChecked . ' />';
        $html        .= dgettext('tuleap-docman', 'Send me an email whenever this item is updated.');
        $html        .= '</td>';
        $html        .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2">';
        $html .= '<input type="submit" value="' . dgettext('tuleap-docman', 'Send my review') . '">';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        $html .= '</form>';

        return $html;
    }

    public function getTableHistory()
    {
        $html = '';
        $uh   = UserHelper::instance();
        if (is_a($this->table, 'Docman_ApprovalTableVersionned')) {
            $html       .= '<h3>' . dgettext('tuleap-docman', 'Approval table history') . '</h3>';
            $html       .= html_build_list_table_top([dgettext('tuleap-docman', 'Document version'),
                dgettext('tuleap-docman', 'Owner'),
                dgettext('tuleap-docman', 'Status'),
                dgettext('tuleap-docman', 'Start date'),
            ]);
            $allTables   = $this->atf->getAllApprovalTable();
            $rowColorIdx = 1;
            foreach ($allTables as $table) {
                $html .= '<tr class="' . html_get_alt_row_color($rowColorIdx++) . '">';
                if ($this->table->getVersionNumber() != $table->getVersionNumber()) {
                    $url  = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        [
                            'action'  => 'details',
                            'section' => 'approval',
                            'id'      => $this->item->getId(),
                            'version' => $table->getVersionNumber(),
                        ]
                    );
                    $href = '<a href="' . $url . '">' . $table->getVersionNumber() . '</a>';
                } else {
                    $href = $table->getVersionNumber();
                }
                $approval_state = '';
                switch ($table->getApprovalState()) {
                    case PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET:
                        $approval_state = dgettext('tuleap-docman', 'Not Yet');
                        break;
                    case PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED:
                        $approval_state = dgettext('tuleap-docman', 'Approved');
                        break;
                    case PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED:
                        $approval_state = dgettext('tuleap-docman', 'Rejected');
                        break;
                    case PLUGIN_DOCMAN_APPROVAL_STATE_COMMENTED:
                        $approval_state = dgettext('tuleap-docman', 'Comment only');
                        break;
                    case PLUGIN_DOCMAN_APPROVAL_STATE_DECLINED:
                        $approval_state = dgettext('tuleap-docman', 'Will not review');
                        break;
                }

                $html .= '<td>' . $href . '</td>';
                $html .= '<td>' . $this->hp->purify($uh->getDisplayNameFromUserId($table->getOwner())) . '</td>';
                $html .= '<td>' . $approval_state . '</td>';
                $html .= '<td>' . DateHelper::formatForLanguage($GLOBALS['Language'], $table->getDate()) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    public function getToolbar()
    {
        $html = '';
        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if ($dpm->userCanWrite($user, $this->item->getId())) {
            $url       = DocmanViewURLBuilder::buildActionUrl(
                $this->item,
                ['default_url' => $this->url],
                ['action' => 'approval_create', 'id' => $this->item->getId()]
            );
            $adminLink = '<a href="' . $url . '">' . dgettext('tuleap-docman', 'Admin') . '</a>';
            $html      = '<strong>' . $adminLink . '</strong><br />';
        }
        return $html;
    }

    public function getContent($params = [])
    {
        $html = '';

        $user = $this->_getCurrentUser();
        $dpm  = $this->_getPermissionsManager();
        if (! $dpm->userCanRead($user, $this->item->getId())) {
            return $html;
        }

        if (is_a($this->item, 'Docman_Empty')) {
            $html = dgettext('tuleap-docman', 'This is not possible to create approval table for Empty documents.');
            return $html;
        }

        $this->initDisplay();

        $request = HTTPRequest::instance();

        // Show toolbar
        $html .= $this->getToolbar();

        // Show Content
        if ($this->table === null) {
            $html .= '<p>';
            $html .= dgettext('tuleap-docman', 'No approval table for this document.');
            if ($dpm->userCanWrite($user, $this->item->getId())) {
                $url       = DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'approval_create', 'id' => $this->item->getId()]
                );
                $adminLink = '<a href="' . $url . '">' . dgettext('tuleap-docman', 'Create a new one.') . '</a>';
                $html     .= ' <strong>' . $adminLink . '</strong><br />';
            }
            $html .= '</p>';
        } elseif ($this->table->isDisabled()) {
            $html .= '<p>';
            $html .= dgettext('tuleap-docman', 'The approval table is not yet available.');
            $html .= '</p>';
            $html .= $this->getTableHistory();
        } else {
            // '&user_id=XX' was used in CX_3_4 to identify users. Now it's '&review=1'
            // We should keep this part of the test until CX_3_8.
            if (
                ($request->exist('review') || $request->exist('user_id'))
                && $this->table->isReviewer($user->getId())
                && $this->table->isEnabled()
            ) {
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

    public function &_getDocmanIcons()
    {
        $icons = new Docman_Icons($this->themePath . '/images/ic/');
        return $icons;
    }

    public function &_getUserManager()
    {
        $um = UserManager::instance();
        return $um;
    }

    public function &_getCurrentUser()
    {
        $um   = $this->_getUserManager();
        $user = $um->getCurrentUser();
        return $user;
    }

    public function &_getPermissionsManager()
    {
        $dpm = Docman_PermissionsManager::instance($this->item->getGroupId());
        return $dpm;
    }

    public function visitFolder($item, $params = [])
    {
        return '';
    }

    public function visitWiki($item, $params = [])
    {
        return '';
    }

    public function visitLink($item, $params = [])
    {
        return '';
    }

    public function visitFile($item, $params = [])
    {
        return '';
    }

    public function visitEmbeddedFile($item, $params = [])
    {
        return '';
    }

    public function visitEmpty($item, $params = [])
    {
        return '';
    }
}
