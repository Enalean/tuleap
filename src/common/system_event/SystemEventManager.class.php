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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('common/dao/SystemEventDao.class.php');
require_once('common/dao/CodendiDataAccess.class.php');
require_once('common/event/EventManager.class.php');

// Events
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
        case 'register_project_creation':
            $sysevent = new SystemEvent(SystemEvent::PROJECT_CREATE,$params['group_id'],SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_is_deleted':
            $sysevent = new SystemEvent(SystemEvent::PROJECT_DELETE,$params['group_id'],SystemEvent::PRIORITY_LOW);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_add_user':
            $sysevent = new SystemEvent(SystemEvent::MEMBERSHIP_CREATE, $this->concatParameters($params, array('group_id', 'user_id')), SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_remove_user':
            $sysevent = new SystemEvent(SystemEvent::MEMBERSHIP_DELETE, $this->concatParameters($params, array('group_id', 'user_id')), SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_activate_user':
            $sysevent = new SystemEvent(SystemEvent::USER_CREATE,$params['user_id'],SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_delete_user':
            $sysevent = new SystemEvent(SystemEvent::USER_DELETE,$params['user_id'],SystemEvent::PRIORITY_LOW);
            $this->dao->store($sysevent);
            break;
        case 'cvs_is_private':
            $params['cvs_is_private'] = $params['cvs_is_private'] ? 1 : 0;
            $sysevent = new SystemEvent(SystemEvent::CVS_IS_PRIVATE, $this->concatParameters($params, array('group_id', 'cvs_is_private')), SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_ugroup_creation':
        case 'project_admin_ugroup_creation':
        case 'project_admin_ugroup_edition':
        case 'project_admin_ugroup_remove_user':
        case 'project_admin_ugroup_add_user':
        case 'project_admin_ugroup_deletion':
            $sysevent = new SystemEvent(SystemEvent::UGROUP_MODIFY,
                                        $this->concatParameters($params, array('group_id', 'ugroup_id')),
                                        SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'project_admin_remove_user_from_project_ugroups':
            // multiple ugroups
            // We create several events for coherency. However, the current UGROUP_MODIFY event
            // only needs to be called once per project 
            //(TODO: cache information to avoid multiple file edition? Or consume all other UGROUP_MODIFY events?)
            foreach ($params['ugroups'] as $ugroup_id) {
                $sysevent = new SystemEvent(SystemEvent::UGROUP_MODIFY,
                                            $this->concatParameters($params, array('group_id', 'ugroup_id')),
                                            SystemEvent::PRIORITY_MEDIUM);
                $this->dao->store($sysevent);
            }
            break;
        case 'project_is_private':
            $params['project_is_private'] = $params['project_is_private'] ? 1 : 0;
            $sysevent = new SystemEvent(SystemEvent::PROJECT_IS_PRIVATE, $this->concatParameters($params, array('group_id', 'project_is_private')), SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'mail_list_create':
            $sysevent = new SystemEvent(SystemEvent::MAILING_LIST_CREATE,$params['group_list_id'],SystemEvent::PRIORITY_MEDIUM);
            $this->dao->store($sysevent);
            break;
        case 'mail_list_delete':
            $sysevent = new SystemEvent(SystemEvent::MAILING_LIST_DELETE,$params['group_list_id'],SystemEvent::PRIORITY_LOW);
            $this->dao->store($sysevent);
            break;
        default:
            break;
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

                switch ($row['type']) {
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
                    $sysevent = new $klass($row['id'], $row['parameters'], $row['priority'], $row['status']);
                    break;
                default:              
                    $sysevent = null;
                    break;
                }

                // Process $sysevent
                if (isset($sysevent) && ($sysevent)) {
                    $sysevent->process();
                    $this->dao->close($sysevent);
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
        }
    }
    
    /**
     * Compute a html table to display the status of the last n events
     * @param int $nb the number of event to includ in the table
     */
    public function fetchLastEventsStatus($nb = 10) {
        $html = '';
        $html .= '<table width="100%">';
        $i = 0;
        foreach($this->dao->searchLastEvents($nb) as $row) {
            $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
            
            //id
            $html .= '<td>'. $row['id'] .'</td>';
            
            //name of the event
            $html .= '<td>'. $row['type'] .'</td>';
            
            //status
            $html .= '<td class="system_event_status_'. $row['status'] .'">';
            $html .= $row['status'];
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

}

?>
