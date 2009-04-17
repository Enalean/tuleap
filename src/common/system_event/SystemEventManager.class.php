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
 *
 * 
 */
require_once('common/dao/SystemEventDao.class.php');
require_once('common/dao/CodendiDataAccess.class.php');
require_once('common/event/EventManager.class.php');

// Events
require_once('common/system_event/include/SystemEvent_SYSTEM_CHECK.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_USER_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_USER_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_MAILING_LIST_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_MAILING_LIST_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_CVS_IS_PRIVATE.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_IS_PRIVATE.class.php');

// Backends
require_once('common/backend/Backend.class.php');
require_once('common/backend/BackendSystem.class.php');
require_once('common/backend/BackendAliases.class.php');
require_once('common/backend/BackendSVN.class.php');
require_once('common/backend/BackendCVS.class.php');
require_once('common/backend/BackendMailingList.class.php');


/**
* Manager of system events
*
* Base class to manage system events
*/
class SystemEventManager {
    
    var $dao;

    // Constructor
    function SystemEventManager() {
        $this->_getDao();

        $event_manager = $this->_getEventManager();
        $events_to_listen = array(
            'system_check', 
            Event::EDIT_SSH_KEYS,
            'register_project_creation',
            'project_is_deleted',
            'project_admin_add_user',
            'project_admin_remove_user',
            'project_admin_activate_user',
            'project_admin_delete_user',
            'cvs_is_private',
            'project_is_private',
            'project_admin_ugroup_creation',
            'project_admin_ugroup_edition',
            'project_admin_ugroup_remove_user',
            'project_admin_ugroup_add_user',
            'project_admin_ugroup_deletion',
            'project_admin_remove_user_from_project_ugroups',
            'mail_list_create',
            'mail_list_delete'
            );
        foreach($events_to_listen as $event) {
            $event_manager->addListener($event, $this, 'addSystemEvent', true, 0);
        }
    }

    protected static $_instance;
    /**
     * SystemEventManager is singleton
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    function _getEventManager() {
        return EventManager::instance();
    }

    function _getDao() {
        if (!$this->dao) {
            $this->dao = new SystemEventDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }

    function _getBackend() {
        return Backend::instance();
    }

    /*
     * Convert selected event into a system event, and store it accordingly
     */
    function addSystemEvent($event, $params) {
        //$event = constant(strtoupper($event));
        switch ($event) {
        case 'system_check':
            // TODO: check that there is no already existing system_check job?
            $this->createEvent(SystemEvent::SYSTEM_CHECK,
                               '',
                               SystemEvent::PRIORITY_LOW);
            break;
        case Event::EDIT_SSH_KEYS:
            $this->createEvent(SystemEvent::EDIT_SSH_KEYS,
                               $params['user_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'register_project_creation':
            $this->createEvent(SystemEvent::PROJECT_CREATE,
                               $params['group_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_is_deleted':
            $this->createEvent(SystemEvent::PROJECT_DELETE,
                               $params['group_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case 'project_admin_add_user':
            $this->createEvent(SystemEvent::MEMBERSHIP_CREATE, 
                               $this->concatParameters($params, array('group_id', 'user_id')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_remove_user':
            $this->createEvent(SystemEvent::MEMBERSHIP_DELETE, 
                               $this->concatParameters($params, array('group_id', 'user_id')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_activate_user':
            $this->createEvent(SystemEvent::USER_CREATE,
                               $params['user_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_delete_user':
            $this->createEvent(SystemEvent::USER_DELETE,
                               $params['user_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case 'cvs_is_private':
            $params['cvs_is_private'] = $params['cvs_is_private'] ? 1 : 0;
            $this->createEvent(SystemEvent::CVS_IS_PRIVATE, 
                               $this->concatParameters($params, array('group_id', 'cvs_is_private')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_ugroup_creation':
        case 'project_admin_ugroup_creation':
        case 'project_admin_ugroup_edition':
        case 'project_admin_ugroup_remove_user':
        case 'project_admin_ugroup_add_user':
        case 'project_admin_ugroup_deletion':
            $this->createEvent(SystemEvent::UGROUP_MODIFY,
                               $this->concatParameters($params, array('group_id', 'ugroup_id')),
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_remove_user_from_project_ugroups':
            // multiple ugroups
            // We create several events for coherency. However, the current UGROUP_MODIFY event
            // only needs to be called once per project 
            //(TODO: cache information to avoid multiple file edition? Or consume all other UGROUP_MODIFY events?)
            foreach ($params['ugroups'] as $ugroup_id) {
                $this->createEvent(SystemEvent::UGROUP_MODIFY,
                                   $this->concatParameters($params, array('group_id', 'ugroup_id')),
                                   SystemEvent::PRIORITY_MEDIUM);
            }
            break;
        case 'project_is_private':
            $params['project_is_private'] = $params['project_is_private'] ? 1 : 0;
            $this->createEvent(SystemEvent::PROJECT_IS_PRIVATE, 
                               $this->concatParameters($params, array('group_id', 'project_is_private')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'mail_list_create':
            $this->createEvent(SystemEvent::MAILING_LIST_CREATE,
                               $params['group_list_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'mail_list_delete':
            $this->createEvent(SystemEvent::MAILING_LIST_DELETE,
                               $params['group_list_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        default:
            break;
        }
    }
    
    /**
     * Create a new event, store it in the db and send notifications
     */
    protected function createEvent($type, $parameters, $priority) {
        if ($id = $this->dao->store($type, $parameters, $priority, SystemEvent::STATUS_NEW, $_SERVER['REQUEST_TIME'])) {
            $sysevent = new SystemEvent($id, 
                                        $type, 
                                        $parameters,
                                        $priority, 
                                        SystemEvent::STATUS_NEW, 
                                        $_SERVER['REQUEST_TIME'], 
                                        null, 
                                        null,
                                        null);
            $sysevent->notify();
        }
    }
    
    /**
     * Concat parameters as $params['key1'] . SEPARATOR . $params['key3'] ...
     * @param array $params
     * @param array $keys array('key1', 'key3')
     */
    public function concatParameters($params, $keys) {
        $concat = array();
        foreach($keys as $key) {
            $concat[] = $params[$key];
        }
        return implode(SystemEvent::PARAMETER_SEPARATOR, $concat);
    }
    
    /**
     * Process stored events. Should this be moved to a new class?
     */
    function processEvents() {
        while (($dar=$this->dao->checkOutNextEvent()) != null) {
            if ($row = $dar->getRow()) {
                //echo "Processing event ".$row['id']." (".$row['type'].")\n";
                $sysevent = null;
                switch ($row['type']) {
                case SystemEvent::SYSTEM_CHECK:
                case SystemEvent::EDIT_SSH_KEYS:
                case SystemEvent::PROJECT_CREATE:
                case SystemEvent::PROJECT_DELETE:
                case SystemEvent::MEMBERSHIP_CREATE:
                case SystemEvent::MEMBERSHIP_DELETE:
                case SystemEvent::UGROUP_MODIFY:
                case SystemEvent::USER_CREATE:
                case SystemEvent::USER_DELETE:
                case SystemEvent::MAILING_LIST_CREATE:
                case SystemEvent::MAILING_LIST_DELETE:
                case SystemEvent::CVS_IS_PRIVATE:
                case SystemEvent::PROJECT_IS_PRIVATE:
                    $klass = 'SystemEvent_'. $row['type'];
                    $sysevent = new $klass($row['id'], 
                                           $row['type'], 
                                           $row['parameters'], 
                                           $row['priority'],
                                           $row['status'], 
                                           $row['create_date'], 
                                           $row['process_date'], 
                                           $row['end_date'],
                                           $row['log']);
                    break;
                default:              
                    break;
                }

                // Process $sysevent
                if ($sysevent) {
                    $sysevent->process();
                    $this->dao->close($sysevent);
                    $sysevent->notify();
                    // Output errors???
                }
            }
        }
        // Since generating aliases may be costly, do it only once everything else is processed
        if (BackendAliases::instance()->aliasesNeedUpdate()) {
            BackendAliases::instance()->update();
        }

        // Update CVS root allow file once everything else is processed
        if (BackendCVS::instance()->getCVSRootListNeedUpdate()) {
            BackendCVS::instance()->CVSRootListUpdate();
        }

        // Update SVN root definition for Apache once everything else is processed
        if (BackendSVN::instance()->getSVNApacheConfNeedUpdate()) {
            BackendSVN::instance()->generateSVNApacheConf();
            // Need to refresh apache (reload)
            system('/sbin/service httpd reload');
        }
    }
    
    /**
     * Compute a html table to display the status of the last n events
     * @param int $offset the offset of the pagination
     * @param int $limit the number of event to includ in the table
     * @param boolean $full display a full table or only a summary
     */
    public function fetchLastEventsStatus($offset = 0, $limit = 10, $full = false) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<table width="100%">';
        
        if ($full) {
            $html .= '<thead><tr>';
            $html .= '<th class="boxtitle">'. 'id' .'</td>';
            $html .= '<th class="boxtitle">'. 'type' .'</td>';
            $html .= '<th class="boxtitle" style="text-align:center">'. 'status' .'</th>';
            $html .= '<th class="boxtitle" style="text-align:center">'. 'priority' .'</th>';
            $html .= '<th class="boxtitle">'. 'parameters' .'</th>';
            $html .= '<th class="boxtitle">'. 'create_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'process_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'end_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'log' .'</th>';
            
            $html .= '</tr></thead>';
            
        }
        $html .= '<tbody>';
        
        $i = 0;
        foreach($this->dao->searchLastEvents($offset, $limit) as $row) {
            $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
            
            //id
            $html .= '<td>'. $row['id'] .'</td>';
            
            //name of the event
            $html .= '<td>'. $row['type'] .'</td>';
            
            //status
            $html .= '<td class="system_event_status_'. $row['status'] .'"';
            if ($row['log']) {
                $html .= ' title="'. $hp->purify($row['log'], CODENDI_PURIFIER_CONVERT_HTML) .'" ';
            }
            $html .= '>';
            $html .= $row['status'];
            $html .= '</td>';
            
            if ($full) {
                $html .= '<td style="text-align:center">'. $row['priority'] .'</td>';
                $html .= '<td>'. $row['parameters'] .'</td>';
                $html .= '<td>'. $row['create_date'] .'</td>';
                $html .= '<td>'. $row['process_date'] .'</td>';
                $html .= '<td>'. $row['end_date'] .'</td>';
                $html .= '<td>'. $row['log'] .'</td>';
            }
            
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        if ($full) {
            //Pagination
            list(,$num_total_rows) = each($this->dao->retrieve("SELECT FOUND_ROWS() AS nb")->getRow());
            
            $nb_of_pages = ceil($num_total_rows / $limit);
            $current_page = round($offset / $limit);
            $html .= '<div style="font-family:Verdana">Page: ';
            $width = 10;
            for ($i = 0 ; $i < $nb_of_pages ; ++$i) {
                if ($i == 0 || $i == $nb_of_pages - 1 || ($current_page - $width / 2 <= $i && $i <= $width / 2 + $current_page)) {
                    $html .= '<a href="?'.
                        'offset='. (int)($i * $limit) .
                        '">';
                    if ($i == $current_page) {
                        $html .= '<b>'. ($i + 1) .'</b>';
                    } else {
                        $html .= $i + 1;
                    }
                    $html .= '</a>&nbsp;';
                } else if ($current_page - $width / 2 - 1 == $i || $current_page + $width / 2 + 1 == $i) {
                    $html .= '...&nbsp;';
                }
            }
            echo '</div>';
        
        }
        return $html;
    }

}

?>
