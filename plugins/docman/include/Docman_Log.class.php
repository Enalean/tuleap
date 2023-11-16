<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
use Tuleap\Docman\Log\LogRetriever;

/**
 * Log is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Log
{
    /* implements EventListener */

    public function __construct()
    {
        $this->_getDao();
    }

    public function log($event, $params)
    {
        $event = constant(strtoupper($event));
        switch ($event) {
            case PLUGIN_DOCMAN_EVENT_EDIT:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, 'old', 'new');
                break;
            case PLUGIN_DOCMAN_EVENT_MOVE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['item']->getParentId(), $params['parent']->getId());
                break;
            case PLUGIN_DOCMAN_EVENT_NEW_VERSION:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, null, $params['version']->getId());
                break;
            case PLUGIN_DOCMAN_EVENT_DEL_VERSION:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['old_value'], null);
                break;
            case PLUGIN_DOCMAN_EVENT_METADATA_UPDATE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['old_value'], $params['new_value'], $params['field']);
                break;
            case PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['old_value'], $params['new_value']);
                break;
            case PLUGIN_DOCMAN_EVENT_SET_VERSION_AUTHOR:
            case PLUGIN_DOCMAN_EVENT_SET_VERSION_DATE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, null, $params['new_value']);
                break;
            case PLUGIN_DOCMAN_EVENT_RESTORE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, null, null);
                break;
            case PLUGIN_DOCMAN_EVENT_RESTORE_VERSION:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['old_value'], null);
                break;
            default:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event);
                break;
        }
    }

    public function logsDaily($params)
    {
        $params['logs'][] = [
            'sql'   => $this->dao->getSqlStatementForLogsDaily($params['group_id'], $params['logs_cond']),
            'field' => dgettext('tuleap-docman', 'Documents'),
            'title' => dgettext('tuleap-docman', 'Document Access'),
        ];
    }

    public $dao;

    public function _getDao()
    {
        if (! $this->dao) {
            $this->dao = new Docman_LogDao(CodendiDataAccess::instance());
        }

        return $this->dao;
    }

    public $dif;

    public function _getItemFactory($group_id)
    {
        $this->dif = new Docman_ItemFactory($group_id);

        return $this->dif;
    }

    public function fetchLogsForItem(\Docman_Item $item, $display_access_logs, \PFUser $current_user): string
    {
        $html          = '';
        $uh            = UserHelper::instance();
        $hp            = Codendi_HTMLPurifier::instance();
        $html         .= '<h3>' . dgettext('tuleap-docman', 'Document History') . '</h3>';
        $log_retriever = new LogRetriever(
            new \Tuleap\Docman\Log\LogDao(),
            UserManager::instance(),
            new Docman_MetadataListOfValuesElementFactory(),
        );

        $log = $log_retriever->getLogForItemId($item, (bool) $display_access_logs);
        if (empty($log)) {
            $html .= '<div>' . dgettext('tuleap-docman', 'There is no history yet') . '</div>';
        } else {
            $titles   = [];
            $titles[] = dgettext('tuleap-docman', 'When');
            $titles[] = dgettext('tuleap-docman', 'Who');
            $titles[] = dgettext('tuleap-docman', 'What');
            $titles[] = dgettext('tuleap-docman', 'Old Value');
            $titles[] = dgettext('tuleap-docman', 'New Value');
            $html    .= html_build_list_table_top($titles, false, false, false, null, "table");

            foreach ($log as $entry) {
                $html   .= '<tr>';
                $html   .= '<td>' . DateHelper::relativeDateBlockContext($entry->when->getTimestamp(), $current_user) . '</td>';
                $html   .= '<td>' . $hp->purify($uh->getDisplayNameFromUser($entry->who)) . '</td>';
                $html   .= '<td>' . $hp->purify($entry->what) . '</td>';
                $colspan = ($entry->old_value === null || $entry->new_value === null) ? 2 : 1;
                if ($entry->old_value !== null) {
                    $html .= '<td colspan="' . $colspan . '">' . $hp->purify($entry->old_value) . '</td>';
                }
                if ($entry->new_value !== null) {
                    $html .= '<td colspan="' . $colspan . '">' . $hp->purify($entry->new_value) . '</td>';
                }
                if ($entry->old_value === null && $entry->new_value === null) {
                    $html .= '<td colspan="2">';
                    if ($entry->diff_link) {
                        $html .= '<a href="' . $hp->purify($entry->diff_link) . '">diffs</a>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
        }

        return $html;
    }

    /**
     * Search if user accessed the given item after the given date.
     */
    public function userAccessedSince($userId, $itemId, $date)
    {
        return $this->dao->searchUserAccessSince($userId, $itemId, $date);
    }
}
