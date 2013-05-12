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
require_once('common/system_event/include/SystemEvent_PROJECT_RENAME.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_MEMBERSHIP_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_USER_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_USER_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_USER_EMAIL_CHANGED.class.php');
require_once('common/system_event/include/SystemEvent_USER_RENAME.class.php');
require_once('common/system_event/include/SystemEvent_MAILING_LIST_CREATE.class.php');
require_once('common/system_event/include/SystemEvent_MAILING_LIST_DELETE.class.php');
require_once('common/system_event/include/SystemEvent_CVS_IS_PRIVATE.class.php');
require_once('common/system_event/include/SystemEvent_PROJECT_IS_PRIVATE.class.php');
require_once('common/system_event/include/SystemEvent_SERVICE_USAGE_SWITCH.class.php');
require_once('common/system_event/include/SystemEvent_UGROUP_MODIFY.class.php');
require_once('common/system_event/include/SystemEvent_EDIT_SSH_KEYS.class.php');
require_once('common/system_event/include/SystemEvent_ROOT_DAILY.class.php');
require_once('common/system_event/include/SystemEvent_COMPUTE_MD5SUM.class.php');
require_once('common/system_event/include/SystemEvent_SVN_UPDATE_HOOKS.class.php');

// Backends
require_once('common/backend/Backend.class.php');

/**
* Manager of system events
*
* Base class to manage system events
*/
class SystemEventManager {
    
    var $dao;

    // Constructor
    private function __construct() {
        $this->_getDao();

        $event_manager = $this->_getEventManager();
        $events_to_listen = array(
            Event::SYSTEM_CHECK, 
            Event::USER_EMAIL_CHANGED, 
            Event::EDIT_SSH_KEYS,
            Event::PROJECT_RENAME,
            Event::USER_RENAME,
            Event::COMPUTE_MD5SUM,
            Event::SVN_UPDATE_HOOKS,
            'approve_pending_project',
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
            'mail_list_delete',
            'service_is_used',
            'codendi_daily_start'
            );
        foreach($events_to_listen as $event) {
            $event_manager->addListener($event, $this, 'addSystemEvent', true, 0);
        }
    }

    /**
     * Prevent Clone
     * 
     * @return void
     */
    private function __clone() {
        throw new Exception('Cannot clone singleton');
    }

    protected static $_instance;

    /**
     * SystemEventManager is singleton
     * 
     * @return SystemEventManager
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    public function setInstance(SystemEventManager $instance) {
        self::$_instance = $instance;
    }

    public function clearInstance() {
        self::$_instance = null;
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
        return Backend::instance('Backend');
    }

    /*
     * Convert selected event into a system event, and store it accordingly
     */
    function addSystemEvent($event, $params) {
        //$event = constant(strtoupper($event));
        switch ($event) {
        case Event::SYSTEM_CHECK:
            // TODO: check that there is no already existing system_check job?
            $this->createEvent(SystemEvent::TYPE_SYSTEM_CHECK,
                               '',
                               SystemEvent::PRIORITY_LOW);
            break;
        case Event::EDIT_SSH_KEYS:
            $this->createEvent(SystemEvent::TYPE_EDIT_SSH_KEYS,
                               $this->concatParameters($params, array('user_id', 'original_keys')),
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case Event::USER_EMAIL_CHANGED:
            $this->createEvent(SystemEvent::TYPE_USER_EMAIL_CHANGED,
                               $params['user_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case 'approve_pending_project':
            $this->createEvent(SystemEvent::TYPE_PROJECT_CREATE,
                               $params['group_id'], 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_is_deleted':
            $this->createEvent(SystemEvent::TYPE_PROJECT_DELETE,
                               $params['group_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case Event::PROJECT_RENAME:
            $this->createEvent(SystemEvent::TYPE_PROJECT_RENAME,
                               $this->concatParameters($params, array('group_id', 'new_name')),
                               SystemEvent::PRIORITY_HIGH);
            break;
        case 'project_admin_add_user':
            $this->createEvent(SystemEvent::TYPE_MEMBERSHIP_CREATE, 
                               $this->concatParameters($params, array('group_id', 'user_id')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_remove_user':
            $this->createEvent(SystemEvent::TYPE_MEMBERSHIP_DELETE, 
                               $this->concatParameters($params, array('group_id', 'user_id')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_activate_user':
            $this->createEvent(SystemEvent::TYPE_USER_CREATE,
                               $params['user_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_delete_user':
            $this->createEvent(SystemEvent::TYPE_USER_DELETE,
                               $params['user_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case Event::USER_RENAME:
            $this->createEvent(SystemEvent::TYPE_USER_RENAME,
                               $this->concatParameters($params, array('user_id', 'new_name')),
                               SystemEvent::PRIORITY_HIGH);
            break;
        case 'cvs_is_private':
            $params['cvs_is_private'] = $params['cvs_is_private'] ? 1 : 0;
            $this->createEvent(SystemEvent::TYPE_CVS_IS_PRIVATE, 
                               $this->concatParameters($params, array('group_id', 'cvs_is_private')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_is_private':
            $params['project_is_private'] = $params['project_is_private'] ? 1 : 0;
            $this->createEvent(SystemEvent::TYPE_PROJECT_IS_PRIVATE, 
                               $this->concatParameters($params, array('group_id', 'project_is_private')), 
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_ugroup_edition':
            $this->createEvent(SystemEvent::TYPE_UGROUP_MODIFY,
                              $this->concatParameters($params, array('group_id', 'ugroup_id', 'ugroup_name', 'ugroup_old_name')),
                              SystemEvent::PRIORITY_MEDIUM);
                        break;
        case 'project_admin_ugroup_creation':
        case 'project_admin_ugroup_remove_user':
        case 'project_admin_ugroup_add_user':
        case 'project_admin_ugroup_deletion':
            $this->createEvent(SystemEvent::TYPE_UGROUP_MODIFY,
                               $this->concatParameters($params, array('group_id', 'ugroup_id')),
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'project_admin_remove_user_from_project_ugroups':
            // multiple ugroups
            // We create several events for coherency. However, the current UGROUP_MODIFY event
            // only needs to be called once per project 
            //(TODO: cache information to avoid multiple file edition? Or consume all other UGROUP_MODIFY events?)
            foreach ($params['ugroups'] as $ugroup_id) {
                $params['ugroup_id'] = $ugroup_id;
                $this->createEvent(SystemEvent::TYPE_UGROUP_MODIFY,
                                   $this->concatParameters($params, array('group_id', 'ugroup_id')),
                                   SystemEvent::PRIORITY_MEDIUM);
            }
            break;
        case 'mail_list_create':
            $this->createEvent(SystemEvent::TYPE_MAILING_LIST_CREATE,
                               $params['group_list_id'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'mail_list_delete':
            $this->createEvent(SystemEvent::TYPE_MAILING_LIST_DELETE,
                               $params['group_list_id'],
                               SystemEvent::PRIORITY_LOW);
            break;
        case 'service_is_used':
            $this->createEvent(SystemEvent::TYPE_SERVICE_USAGE_SWITCH,
                               $this->concatParameters($params, array('group_id', 'shortname', 'is_used')),
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case 'codendi_daily_start':
            $this->createEvent(SystemEvent::TYPE_ROOT_DAILY,
                               '',
                               SystemEvent::PRIORITY_MEDIUM);
            break;
        case Event::COMPUTE_MD5SUM:
            $this->createEvent(SystemEvent::TYPE_COMPUTE_MD5SUM,
                               $params['fileId'],
                               SystemEvent::PRIORITY_MEDIUM);
            break;

        case Event::SVN_UPDATE_HOOKS:
            $this->createEvent(
                SystemEvent::TYPE_SVN_UPDATE_HOOKS,
                $params['group_id'],
                SystemEvent::PRIORITY_MEDIUM
            );
            break;

        default:

            break;
        }
    }
    
    /**
     * Create a new event, store it in the db and send notifications
     */
    public function createEvent($type, $parameters, $priority,$owner=SystemEvent::OWNER_ROOT) {
        if ($id = $this->dao->store($type, $parameters, $priority, SystemEvent::STATUS_NEW, $_SERVER['REQUEST_TIME'],$owner)) {
            $klass = 'SystemEvent_'. $type;
            $sysevent = new $klass($id, 
                                   $type,
                                   $owner,
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
     * Instantiate a SystemEvent from a row
     *
     * @param array $row The data of the event
     *
     * @return SystemEvent
     */
    public function getInstanceFromRow($row) {
        $em           = EventManager::instance();
        $sysevent     = null;
        $klass        = null;
        $klass_params = null;
        switch ($row['type']) {
        case SystemEvent::TYPE_SYSTEM_CHECK:
        case SystemEvent::TYPE_EDIT_SSH_KEYS:
        case SystemEvent::TYPE_PROJECT_CREATE:
        case SystemEvent::TYPE_PROJECT_DELETE:
        case SystemEvent::TYPE_PROJECT_RENAME:
        case SystemEvent::TYPE_MEMBERSHIP_CREATE:
        case SystemEvent::TYPE_MEMBERSHIP_DELETE:
        case SystemEvent::TYPE_UGROUP_MODIFY:
        case SystemEvent::TYPE_USER_CREATE:
        case SystemEvent::TYPE_USER_DELETE:
        case SystemEvent::TYPE_USER_EMAIL_CHANGED:
        case SystemEvent::TYPE_USER_RENAME:
        case SystemEvent::TYPE_MAILING_LIST_CREATE:
        case SystemEvent::TYPE_MAILING_LIST_DELETE:
        case SystemEvent::TYPE_CVS_IS_PRIVATE:
        case SystemEvent::TYPE_PROJECT_IS_PRIVATE:
        case SystemEvent::TYPE_SERVICE_USAGE_SWITCH:
        case SystemEvent::TYPE_ROOT_DAILY:
        case SystemEvent::TYPE_COMPUTE_MD5SUM:
            $klass = 'SystemEvent_'. $row['type'];
            break;

        case SystemEvent::TYPE_SVN_UPDATE_HOOKS:
            $klass = 'SystemEvent_'. $row['type'];
            $klass_params = array(Backend::instance(Backend::SVN));
            break;

        default:
            $em->processEvent(Event::GET_SYSTEM_EVENT_CLASS, array('type' => $row['type'], 'class' => &$klass, 'dependencies' => &$klass_params));
            break;
        }
        if (class_exists($klass)) {
            $sysevent = new $klass($row['id'],
                                   $row['type'],
                                   $row['owner'],
                                   $row['parameters'],
                                   $row['priority'],
                                   $row['status'],
                                   $row['create_date'],
                                   $row['process_date'],
                                   $row['end_date'],
                                   $row['log']);
            if (!empty($klass_params)) {
                call_user_func_array(array($sysevent, 'injectDependencies'), $klass_params);
            }
        }
        return $sysevent;
    }
    
    
    /**
     * @return array
     */
    public function getTypes() {
        $reflect = new ReflectionClass('SystemEvent');
        $consts  = $reflect->getConstants();
        array_walk($consts, array($this, 'filterConstants'));
        $types = array_filter($consts);
        EventManager::instance()->processEvent(Event::SYSTEM_EVENT_GET_TYPES, array('types' => &$types));
        return $types;
    }
    
    protected function filterConstants(&$item, $key) {
        if (strpos($key, 'TYPE_') !== 0) {
            $item = null;
        }
    }
    
    /**
     * Compute a html table to display the status of the last n events
     * 
     * @param int                   $offset        the offset of the pagination
     * @param int                   $limit         the number of event to includ in the table
     * @param boolean               $full          display a full table or only a summary
     * @param array                 $filter_status the filter on status
     * @param array                 $filter_type   the filter on type
     * @param CSRFSynchronizerToken $csrf          The token to use to build actions on events
     *
     * @return string html
     */
    public function fetchLastEventsStatus($offset = 0, $limit = 10, $full = false, $filter_status = false, $filter_type = false, CSRFSynchronizerToken $csrf = null) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<table width="100%">';
        
        if ($full) {
            $html .= '<thead><tr>';
            $html .= '<th class="boxtitle">'. 'id' .'</td>';
            $html .= '<th class="boxtitle">'. 'type' .'</td>';
            $html .= '<th class="boxtitle">'. 'owner' .'</td>';
            $html .= '<th class="boxtitle" style="text-align:center">'. 'status' .'</th>';
            $html .= '<th class="boxtitle" style="text-align:center">'. 'priority' .'</th>';
            $html .= '<th class="boxtitle">'. 'parameters' .'</th>';
            $html .= '<th class="boxtitle">'. 'create_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'process_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'end_date' .'</th>';
            $html .= '<th class="boxtitle">'. 'log' .'</th>';
            $html .= '<th class="boxtitle">'. 'actions' .'</th>';
            
            $html .= '</tr></thead>';
            
        }
        $html .= '<tbody>';
        
        $replay_action_params = array();
        if ($csrf) {
            $replay_action_params[$csrf->getTokenName()] = $csrf->getToken();
        }
        if (!$filter_status) {
            $filter_status = array(
                SystemEvent::STATUS_NEW, 
                SystemEvent::STATUS_RUNNING, 
                SystemEvent::STATUS_DONE, 
                SystemEvent::STATUS_WARNING, 
                SystemEvent::STATUS_ERROR,
            );
        }
        if (!$filter_type) {
            $filter_type = $this->getTypes();
        }
        $i = 0;
        foreach($this->dao->searchLastEvents($offset, $limit, $filter_status, $filter_type) as $row) {
            if ($sysevent = $this->getInstanceFromRow($row)) {
                $html .= '<tr class="'. html_get_alt_row_color($i++) .'">';
                
                //id
                $html .= '<td>'. $sysevent->getId() .'</td>';
                
                //name of the event
                $html .= '<td>'. $sysevent->getType() .'</td>';

                $html .= '<td>'. $sysevent->getOwner() .'</td>';
                
                //status
                $html .= '<td class="system_event_status_'. $row['status'] .'"';
                if ($sysevent->getLog()) {
                    $html .= ' title="'. $hp->purify($sysevent->getLog(), CODENDI_PURIFIER_CONVERT_HTML) .'" ';
                }
                $html .= '>';
                $html .= $sysevent->getStatus();
                $html .= '</td>';
                
                if ($full) {
                    $replay_link = '';
                    if ($sysevent->getStatus() == SystemEvent::STATUS_ERROR) {
                        $replay_action_params['replay'] = $sysevent->getId();
                        $replay_link .= '<a href="/admin/system_events/?'. http_build_query($replay_action_params) .'" title="Replay this event">'; 
                        $replay_link .= $GLOBALS['HTML']->getImage('ic/arrow-circle.png');
                        $replay_link .= '</a>';
                    }

                    $html .= '<td style="text-align:center">'. $sysevent->getPriority() .'</td>';
                    $html .= '<td>'. $sysevent->verbalizeParameters(true) .'</td>';
                    $html .= '<td>'. $sysevent->getCreateDate().'</td>';
                    $html .= '<td>'. $sysevent->getProcessDate() .'</td>';
                    $html .= '<td>'. $sysevent->getEndDate() .'</td>';
                    $html .= '<td>'. nl2br($sysevent->getLog()) .'</td>';
                    $html .= '<td>'. $replay_link .'</td>';
                }
                
                $html .= '</tr>';
            }
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
                    $html .= '<a href="?'. http_build_query(array(
                            'offset'        => (int)($i * $limit),
                            'filter_status' => $filter_status,
                            'filter_type'   => $filter_type,
                        )).
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

    /**
     *
     * @param type $event_type
     * @param type $parameter
     * @return boolean
     */
    public function isThereAnEventAlreadyOnGoing($event_type, $parameter) {
        $dar = $this->_getDao()->searchWithParam(
            'head',
             $parameter,
             array($event_type),
             array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)
        );
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Return true if there is no pending rename event of this user, otherwise false
     * 
     * @param PFUser $user 
     * @return Boolean
     */
    public function canRenameUser($user) {
        return ! $this->isThereAnEventAlreadyOnGoing(SystemEvent::TYPE_USER_RENAME, $user->getId());
    }
    
    /**
     * Return true if there is no pending rename event of this project, otherwise false
     * 
     * @param PFUser $user 
     * @return Boolean
     */
    public function canRenameProject($project) {
        return ! $this->isThereAnEventAlreadyOnGoing(SystemEvent::TYPE_PROJECT_RENAME, $project->getId());
    }
    
    
    /**
     * Return true if there is no pending rename user event on this new name
     * @param String $new_name
     * @return Boolean
     */
    public function isUserNameAvailable($newName) {
        $dar = $this->_getDao()->searchWithParam('tail', $newName, array(SystemEvent::TYPE_USER_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
        if ($dar && !$dar->isError() && $dar->rowCount() == 0) {
            return true;
        }
        return false;
    }
    
    
    /**
     * Return true if there is no pending rename project event on this new name
     * @param String $new_name
     * @return Boolean
     */
    public function isProjectNameAvailable($newName) {
        $dar = $this->_getDao()->searchWithParam('tail', $newName, array(SystemEvent::TYPE_PROJECT_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
        if ($dar && !$dar->isError() && $dar->rowCount() == 0) {
            return true;
        }
        return false;
    }

    /**
     * Reset the status of an event to NEW to replay it
     *
     * @param int $id The id of the event to replay
     *
     * @return bool true if success
     */
    public function replay($id) {
        return $this->_getDao()->resetStatus($id, SystemEvent::STATUS_NEW);
    }
}

?>
