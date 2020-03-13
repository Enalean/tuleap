<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Docman_LogDao.class.php');
require_once('Docman_ItemFactory.class.php');
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
        $params['logs'][] = array(
            'sql'   => $this->dao->getSqlStatementForLogsDaily($params['group_id'], $params['logs_cond']),
            'field' => dgettext('tuleap-docman', 'Documents'),
            'title' => dgettext('tuleap-docman', 'Document Access')
        );
    }

    public $dao;
    public function _getDao()
    {
        if (!$this->dao) {
            $this->dao = new Docman_LogDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }

    public $dif;
    public function _getItemFactory($group_id)
    {
        $this->dif = new Docman_ItemFactory($group_id);
        return $this->dif;
    }

    public function fetchLogsForItem($item_id, $display_access_logs)
    {
        $html = '';
        $uh   = UserHelper::instance();
        $hp   = Codendi_HTMLPurifier::instance();
        $html .= '<h3>' . dgettext('tuleap-docman', 'Document History') . '</h3>';
        $dar = $this->dao->searchByItemIdOrderByTimestamp($item_id);
        if ($dar && !$dar->isError()) {
            if ($dar->valid()) {
                $titles = array();
                $titles[] = dgettext('tuleap-docman', 'When');
                $titles[] = dgettext('tuleap-docman', 'Who');
                $titles[] = dgettext('tuleap-docman', 'What');
                $titles[] = dgettext('tuleap-docman', 'Old Value');
                $titles[] = dgettext('tuleap-docman', 'New Value');
                $html .= html_build_list_table_top($titles, false, false, false);

                $odd_even = array('boxitem', 'boxitemalt');
                $i = 0;
                $_previous_date = -1;
                $_previous_auth = -1;
                while ($dar->valid()) {
                    $row = $dar->current();
                    if ($row['type'] != PLUGIN_DOCMAN_EVENT_ACCESS || $display_access_logs) {
                        $user = $row['user_id'] ? $hp->purify($uh->getDisplayNameFromUserId($row['user_id'])) : dgettext('tuleap-docman', 'Anonymous');
                        $html .= '<tr class="' . $odd_even[$i++ % count($odd_even)] . '">';
                        $html .= '<td>' . html_time_ago($row['time']) . '</td>';
                        $html .= '<td>' . $user                             . '</td>';
                        if ($row['type'] == PLUGIN_DOCMAN_EVENT_METADATA_UPDATE) {
                            $_old_v = $row['old_value'];
                            $_new_v = $row['new_value'];

                            $mdFactory = new Docman_MetadataFactory($row['group_id']);
                            $md = $mdFactory->getFromLabel($row['field']);
                            if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                                $mdlovebo = new Docman_MetadataListOfValuesElementFactory();
                                $_old_e = $mdlovebo->getByElementId($row['old_value'], $md->getLabel());
                                $_new_e = $mdlovebo->getByElementId($row['new_value'], $md->getLabel());
                                if ($_old_e !== null) {
                                    $_old_v = $_old_e->getName();
                                }
                                if ($_new_e !== null) {
                                    $_new_v = $_new_e->getName();
                                }
                            } elseif ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_DATE) {
                                $_old_v = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $_old_v);
                                $_new_v = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $_new_v);
                            }
                            $html .= '<td>' . sprintf(dgettext('tuleap-docman', 'Change <em>%1$s</em>'), $md->getName()) . '</td>';
                            $html .= '<td>' . $_old_v . '</td>';
                            $html .= '<td>' . $_new_v . '</td>';
                        } elseif ($row['type'] == PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE) {
                            $old_version = $row['old_value'];
                            $new_version = $row['new_value'];
                            $dIF = $this->_getItemFactory($row['group_id']);
                            $pagename = $dIF->getItemFromDb($item_id)->getPageName();
                            $difflink =  '/wiki/index.php?group_id=' . $row['group_id'];
                            $difflink .= '&pagename=' . urlencode($pagename) . '&action=diff';
                            $difflink .= '&versions%5b%5d=' . $old_version . '&versions%5b%5d=' . $new_version;
                            $html .= '<td colspan>' . $this->getText($row['type']) . '</td>';
                            $html .= '<td colspan="2" align="center"><a href=' . $difflink . '>diffs</a>';
                        } elseif ($row['type'] == PLUGIN_DOCMAN_EVENT_SET_VERSION_AUTHOR) {
                            $newUser = $row['new_value'];
                            $html .= '<td>' . $this->getText($row['type']) . '</td>';
                            $html .= "<td>&nbsp;</td>";
                            $html .= "<td>$newUser</td>";
                        } elseif ($row['type'] == PLUGIN_DOCMAN_EVENT_SET_VERSION_DATE) {
                            $newDate = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['new_value']);
                            $html .= '<td>' . $this->getText($row['type']) . '</td>';
                            $html .= "<td>&nbsp;</td>";
                            $html .= "<td>$newDate</td>";
                        } elseif ($row['type'] == PLUGIN_DOCMAN_EVENT_DEL_VERSION) {
                            $old_version = $row['old_value'];
                            $html .= '<td>' . $this->getText($row['type']) . '</td>';
                            $html .= '<td colspan="2" align="center">' . $old_version . '</td>';
                        } elseif ($row['type'] == PLUGIN_DOCMAN_EVENT_RESTORE_VERSION) {
                            $versionNumber = $row['old_value'];
                            $html .= '<td>' . $this->getText($row['type']) . '</td>';
                            $html .= '<td colspan="2" align="center">' . $versionNumber . '</td>';
                        } else {
                            $html .= '<td colspan>' . $this->getText($row['type']) . '</td><td colspan="2">&nbsp;</td>';
                        }
                        $html .= '</tr>';

                        $_previous_date = $row['time'];
                        $_previous_auth = $row['user_id'];
                    }
                    $dar->next();
                }
                $html .= '</table>';
            } else {
                $html .= '<div>' . dgettext('tuleap-docman', 'There is no history yet') . '</div>';
            }
        } else {
            $html .= '<div>' . dgettext('tuleap-docman', 'Error while searching document history!') . '</div>';
            $html .= $dar->isError();
        }
        return $html;
    }

    public function getText($type)
    {
        $txt = '';
        switch ($type) {
            case PLUGIN_DOCMAN_EVENT_ADD:
                $txt = dgettext('tuleap-docman', 'Create');
                break;
            case PLUGIN_DOCMAN_EVENT_EDIT:
                $txt = dgettext('tuleap-docman', 'Edit');
                break;
            case PLUGIN_DOCMAN_EVENT_MOVE:
                $txt = dgettext('tuleap-docman', 'Move');
                break;
            case PLUGIN_DOCMAN_EVENT_DEL:
                $txt = dgettext('tuleap-docman', 'Delete');
                break;
            case PLUGIN_DOCMAN_EVENT_DEL_VERSION:
                $txt = dgettext('tuleap-docman', 'Delete version');
                break;
            case PLUGIN_DOCMAN_EVENT_ACCESS:
                $txt = dgettext('tuleap-docman', 'Access');
                break;
            case PLUGIN_DOCMAN_EVENT_NEW_VERSION:
                $txt = dgettext('tuleap-docman', 'New version');
                break;
            case PLUGIN_DOCMAN_EVENT_METADATA_UPDATE:
                $txt = dgettext('tuleap-docman', 'Property change');
                break;
            case PLUGIN_DOCMAN_EVENT_WIKIPAGE_UPDATE:
                $txt = dgettext('tuleap-docman', 'Wiki page content change');
                break;
            case PLUGIN_DOCMAN_EVENT_SET_VERSION_AUTHOR:
                $txt = dgettext('tuleap-docman', 'Version author');
                break;
            case PLUGIN_DOCMAN_EVENT_SET_VERSION_DATE:
                $txt = dgettext('tuleap-docman', 'Version date');
                break;
            case PLUGIN_DOCMAN_EVENT_RESTORE:
                $txt = dgettext('tuleap-docman', 'Restore');
                break;
            case PLUGIN_DOCMAN_EVENT_RESTORE_VERSION:
                $txt = dgettext('tuleap-docman', 'Restore version');
                break;
            case PLUGIN_DOCMAN_EVENT_LOCK_ADD:
                $txt = dgettext('tuleap-docman', 'Locked document');
                break;
            case PLUGIN_DOCMAN_EVENT_LOCK_DEL:
                $txt = dgettext('tuleap-docman', 'Released lock');
                break;
            default:
                break;
        }
        return $txt;
    }

    /**
     * Search if user accessed the given item after the given date.
     */
    public function userAccessedSince($userId, $itemId, $date)
    {
        return $this->dao->searchUserAccessSince($userId, $itemId, $date);
    }
}
