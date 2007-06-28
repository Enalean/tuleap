<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
require_once('Docman_LogDao.class.php');
/**
 * Log is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_Log { /* implements EventListener */

    function Docman_Log() {
        $this->_getDao();
    }
    
    function log($event, $params) {
        switch ($event) {
            case PLUGIN_DOCMAN_EVENT_EDIT:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, 'old', 'new');
                break;
            case PLUGIN_DOCMAN_EVENT_MOVE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['item']->getParentId(), $params['parent']->getId());
                break;
            case PLUGIN_DOCMAN_EVENT_NEW_VERSION:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, null, $params['version']);
                break;
            case PLUGIN_DOCMAN_EVENT_METADATA_UPDATE:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event, $params['old_value'], $params['new_value'], $params['field']);
                break;
            default:
                $this->dao->create($params['group_id'], $params['item']->getId(), $params['user']->getId(), $event);
                break;
        }
    }
    
    function logsDaily($params) {
        $params['logs'][] = array(
            'sql'   => $this->dao->getSqlStatementForLogsDaily($params['group_id'], $params['logs_cond'], PLUGIN_DOCMAN_EVENT_ACCESS),
            'field' => $GLOBALS['Language']->getText('plugin_docman','logsdaily_field'),
            'title' => $GLOBALS['Language']->getText('plugin_docman','logsdaily_title')
        );
    }
    
    var $dao;
    function _getDao() {
        if (!$this->dao) {
            $this->dao = new Docman_LogDao(CodexDataAccess::instance());
        }
        return  $this->dao;
    }
    
    
    function fetchLogsForItem($item_id, $display_access_logs) {
        $html = '';
        $html .= '<h3>'. $GLOBALS['Language']->getText('plugin_docman','details_history_logs') .'</h3>';
        $dar = $this->dao->searchByItemIdOrderByTimestamp($item_id);
        if ($dar && !$dar->isError()) {
            if ($dar->valid()) {
                $titles = array();
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_logs_when');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_logs_who');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_logs_what');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_logs_old_value');
                $titles[] = $GLOBALS['Language']->getText('plugin_docman','details_history_logs_new_value');
                $html .= html_build_list_table_top($titles, false, false, false);

                $odd_even = array('boxitem', 'boxitemalt');
                $i = 0;
                $_previous_date = -1;
                $_previous_auth = -1;
                while ($dar->valid()) {
                    $row = $dar->current();
                    if ($row['type'] != PLUGIN_DOCMAN_EVENT_ACCESS || $display_access_logs) {
                        $user = $row['user_id'] ? user_getname($row['user_id']) : $GLOBALS['Language']->getText('plugin_docman','details_history_anonymous');
                        $html .= '<tr class="'. $odd_even[$i++ % count($odd_even)] .'">';
                        $html .= '<td>'. format_date($GLOBALS['sys_datefmt'], $row['time']) .'</td>';
                        $html .= '<td>'. $user                             .'</td>';
                        if($row['type'] == PLUGIN_DOCMAN_EVENT_METADATA_UPDATE) {
                            $_old_v = $row['old_value'];
                            $_new_v = $row['new_value'];
    
                            $mdFactory = new Docman_MetadataFactory($row['group_id']);
                            $md =& $mdFactory->getFromLabel($row['field']);
                            if($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                                $mdlovebo = new Docman_MetadataListOfValuesElementFactory();
                                $_old_e =& $mdlovebo->getByElementId($row['old_value'], $md->getLabel());
                                $_new_e =& $mdlovebo->getByElementId($row['new_value'], $md->getLabel());
                                if($_old_e !== null) {
                                    $_old_v = $_old_e->getName();
                                }
                                if($_new_e !== null) {
                                    $_new_v = $_new_e->getName();
                                }                            
                            }
                            $html .= '<td>'.$GLOBALS['Language']->getText('plugin_docman','details_history_logs_change_field', array($md->getName())).'</td>';
                            $html .= '<td>'.$_old_v.'</td>';
                            $html .= '<td>'.$_new_v.'</td>';
    
                        }
                        else {                        
                            $html .= '<td colspan>'. $this->getText($row['type']) .'</td><td colspan="2">&nbsp;</td>';
                        }
                        $html .= '</tr>';
    
                        $_previous_date = $row['time'];
                        $_previous_auth = $row['user_id'];
                    }
                    $dar->next();
                }
                $html .= '</table>';
            } else {
                $html .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_logs_no') .'</div>';
            }
        } else {
            $html .= '<div>'. $GLOBALS['Language']->getText('plugin_docman','details_history_logs_error') .'</div>';
            $html .= $dar->isError();
        }
        return $html;
    }
    
    function getText($type) {
        $txt = '';
        switch($type) {
            case PLUGIN_DOCMAN_EVENT_ADD:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_add');
                break;
            case PLUGIN_DOCMAN_EVENT_EDIT:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_edit');
                break;
            case PLUGIN_DOCMAN_EVENT_MOVE:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_move');
                break;
            case PLUGIN_DOCMAN_EVENT_DEL:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_del');
                break;
            case PLUGIN_DOCMAN_EVENT_ACCESS:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_access');
                break;
            case PLUGIN_DOCMAN_EVENT_NEW_VERSION:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_newversion');
                break;
            case PLUGIN_DOCMAN_EVENT_METADATA_UPDATE:
                $txt = $GLOBALS['Language']->getText('plugin_docman','event_metadataupdate');
                break;
            default:
                break;
        }
        return $txt;
    }

}

?>