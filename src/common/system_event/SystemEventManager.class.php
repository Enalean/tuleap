<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 — Present. All Rights Reserved.
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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\SVNCore\Event\UpdateProjectAccessFilesScheduler;
use Tuleap\SVNCore\Event\UpdateProjectAccessFileSystemEvent;
use Tuleap\SVNCore\SVNAuthenticationCacheInvalidator;
use Tuleap\System\ApacheServiceControl;
use Tuleap\System\ServiceControl;
use Tuleap\SystemEvent\SystemEventInstrumentation;
use Tuleap\SystemEvent\SystemEventSVNAuthenticationCacheRefresh;
use Tuleap\Redis;
use Tuleap\SystemEvent\SystemEventUserActiveStatusChange;
use TuleapCfg\Command\ProcessFactory;

/**
* Manager of system events
*
* Base class to manage system events
*/
class SystemEventManager
{
    public $dao;
    public $followers_dao;

    // Constructor
    private function __construct(?SystemEventDao $dao = null, ?SystemEventsFollowersDao $followers_dao = null)
    {
        $this->dao           = $dao;
        $this->followers_dao = $followers_dao;
        $this->_getDao();

        $event_manager    = $this->_getEventManager();
        $events_to_listen = [
            Event::SYSTEM_CHECK,
            Event::PROJECT_RENAME,
            Event::USER_RENAME,
            Event::COMPUTE_MD5SUM,
            Event::MASSMAIL,
            Event::SVN_AUTH_CACHE_CHANGE,
            Event::UPDATE_ALIASES,
            'approve_pending_project',
            ProjectStatusUpdate::NAME,
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
            'project_admin_ugroup_bind_modified',
            'project_admin_remove_user_from_project_ugroups',
            'mail_list_create',
            'mail_list_delete',
            Event::SERVICE_IS_USED,
            'codendi_daily_start',
        ];
        if (ForgeConfig::areUnixUsersAvailableOnSystem()) {
            $events_to_listen[] = Event::EDIT_SSH_KEYS;
        }
        foreach ($events_to_listen as $event) {
            $event_manager->addListener($event, $this, 'addSystemEvent', true);
        }
    }

    /**
     * Prevent Clone
     *
     * @return void
     */
    private function __clone()
    {
        throw new Exception('Cannot clone singleton');
    }

    protected static $_instance;

    /**
     * SystemEventManager is singleton
     *
     * @return SystemEventManager
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    public static function setInstance(SystemEventManager $instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    public function testInstance(SystemEventDao $dao, SystemEventsFollowersDao $followers_dao)
    {
        return new SystemEventManager($dao, $followers_dao);
    }

    public function _getEventManager()
    {
        return EventManager::instance();
    }

    public function _getDao()
    {
        if (! $this->dao) {
            $this->dao = new SystemEventDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    public function _getFollowersDao()
    {
        if (! $this->followers_dao) {
            $this->followers_dao = new SystemEventsFollowersDao(CodendiDataAccess::instance());
        }
        return $this->followers_dao;
    }

    public function _getBackend()
    {
        return Backend::instance('Backend');
    }

    /*
     * Convert selected event into a system event, and store it accordingly
     */
    public function addSystemEvent($event, $params)
    {
        //$event = constant(strtoupper($event));
        if ($event instanceof ProjectStatusUpdate) {
            match ($event->status) {
                Project::STATUS_ACTIVE => $this->createEvent(
                    SystemEvent::TYPE_PROJECT_ACTIVE,
                    $event->project->getID(),
                    SystemEvent::PRIORITY_LOW
                ),
                Project::STATUS_SUSPENDED => $this->createEvent(
                    SystemEvent::TYPE_PROJECT_SVN_AUTHENTICATION_CACHE_REFRESH,
                    $event->project->getID(),
                    SystemEvent::PRIORITY_LOW
                ),
                Project::STATUS_DELETED => $this->createEvent(
                    SystemEvent::TYPE_PROJECT_DELETE,
                    $event->project->getID(),
                    SystemEvent::PRIORITY_LOW
                ),
            };
            return;
        }
        switch ($event) {
            case Event::SYSTEM_CHECK:
                if (! $this->areThereMultipleEventsQueuedMatchingFirstParameter(Event::SYSTEM_CHECK, null)) {
                    $this->createEvent(
                        SystemEvent::TYPE_SYSTEM_CHECK,
                        '',
                        SystemEvent::PRIORITY_LOW
                    );
                }
                break;
            case Event::EDIT_SSH_KEYS:
                $this->createEvent(
                    SystemEvent::TYPE_EDIT_SSH_KEYS,
                    $this->concatParameters($params, ['user_id', 'original_keys']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case SystemEvent::TYPE_MOVE_FRS_FILE:
                $this->createEvent(
                    SystemEvent::TYPE_MOVE_FRS_FILE,
                    $this->concatParameters($params, ['project_path', 'file_id', 'old_path']),
                    SystemEvent::PRIORITY_HIGH
                );
                break;
            case 'approve_pending_project':
                $this->createEvent(
                    SystemEvent::TYPE_PROJECT_CREATE,
                    $params['group_id'],
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case Event::PROJECT_RENAME:
                $this->createEvent(
                    SystemEvent::TYPE_PROJECT_RENAME,
                    $this->concatParameters($params, ['group_id', 'new_name']),
                    SystemEvent::PRIORITY_HIGH
                );
                break;
            case 'project_admin_add_user':
                $this->createEvent(
                    SystemEvent::TYPE_MEMBERSHIP_CREATE,
                    $this->concatParameters($params, ['group_id', 'user_id']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'project_admin_remove_user':
                $this->createEvent(
                    SystemEvent::TYPE_MEMBERSHIP_DELETE,
                    $this->concatParameters($params, ['group_id', 'user_id']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'project_admin_activate_user':
                $this->createEvent(
                    SystemEvent::TYPE_USER_ACTIVE_STATUS_CHANGE,
                    $params['user_id'],
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'project_admin_delete_user':
                $this->createEvent(
                    SystemEvent::TYPE_USER_DELETE,
                    $params['user_id'],
                    SystemEvent::PRIORITY_LOW
                );
                break;
            case Event::USER_RENAME:
                $this->createEvent(
                    SystemEvent::TYPE_USER_RENAME,
                    $this->concatParameters($params, ['user_id', 'new_name', 'old_user']),
                    SystemEvent::PRIORITY_HIGH
                );
                break;
            case 'cvs_is_private':
                $params['cvs_is_private'] = $params['cvs_is_private'] ? 1 : 0;
                $this->createEvent(
                    SystemEvent::TYPE_CVS_IS_PRIVATE,
                    $this->concatParameters($params, ['group_id', 'cvs_is_private']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'project_is_private':
                $params['project_is_private'] = $params['project_is_private'] ? 1 : 0;
                $this->createEvent(
                    SystemEvent::TYPE_PROJECT_IS_PRIVATE,
                    $this->concatParameters($params, ['group_id', 'project_is_private']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'project_admin_ugroup_edition':
                $parameters = $this->concatParameters($params, ['group_id', 'ugroup_id', 'ugroup_name', 'ugroup_old_name']);
                if (! $this->areThereMultipleEventsQueuedMatchingFirstParameter(SystemEvent::TYPE_UGROUP_MODIFY, $parameters)) {
                    $this->createEvent(
                        SystemEvent::TYPE_UGROUP_MODIFY,
                        $parameters,
                        SystemEvent::PRIORITY_MEDIUM
                    );
                }
                break;
            case 'project_admin_ugroup_creation':
            case 'project_admin_ugroup_remove_user':
            case 'project_admin_ugroup_add_user':
            case 'project_admin_ugroup_deletion':
            case 'project_admin_ugroup_bind_modified':
                $parameters = $this->concatParameters($params, ['group_id', 'ugroup_id']);
                if (! $this->areThereMultipleEventsQueuedMatchingFirstParameter(SystemEvent::TYPE_UGROUP_MODIFY, $parameters)) {
                    $this->createEvent(
                        SystemEvent::TYPE_UGROUP_MODIFY,
                        $parameters,
                        SystemEvent::PRIORITY_MEDIUM
                    );
                }
                break;
            case 'project_admin_remove_user_from_project_ugroups':
                // multiple ugroups
                // We create several events for coherency. However, the current UGROUP_MODIFY event
                // only needs to be called once per project
                //(TODO: cache information to avoid multiple file edition? Or consume all other UGROUP_MODIFY events?)
                foreach ($params['ugroups'] as $ugroup_id) {
                    $params['ugroup_id'] = $ugroup_id;
                    $parameters          = $this->concatParameters($params, ['group_id', 'ugroup_id']);
                    if (! $this->areThereMultipleEventsQueuedMatchingFirstParameter(SystemEvent::TYPE_UGROUP_MODIFY, $parameters)) {
                        $this->createEvent(
                            SystemEvent::TYPE_UGROUP_MODIFY,
                            $parameters,
                            SystemEvent::PRIORITY_MEDIUM
                        );
                    }
                }
                break;
            case 'mail_list_create':
                $this->createEvent(
                    SystemEvent::TYPE_MAILING_LIST_CREATE,
                    $params['group_list_id'],
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'mail_list_delete':
                $this->createEvent(
                    SystemEvent::TYPE_MAILING_LIST_DELETE,
                    $params['group_list_id'],
                    SystemEvent::PRIORITY_LOW
                );
                break;
            case Event::SERVICE_IS_USED:
                $this->createEvent(
                    SystemEvent::TYPE_SERVICE_USAGE_SWITCH,
                    $this->concatParameters($params, ['group_id', 'shortname', 'is_used']),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case 'codendi_daily_start':
                $this->createEvent(
                    SystemEvent::TYPE_ROOT_DAILY,
                    '',
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;
            case Event::COMPUTE_MD5SUM:
                $this->createEvent(
                    SystemEvent::TYPE_COMPUTE_MD5SUM,
                    $params['fileId'],
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;

            case Event::MASSMAIL:
                $this->createEvent(
                    SystemEvent::TYPE_MASSMAIL,
                    json_encode($params),
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;

            case Event::SVN_AUTH_CACHE_CHANGE:
                $this->createEvent(
                    SystemEvent::TYPE_SVN_AUTH_CACHE_CHANGE,
                    '',
                    SystemEvent::PRIORITY_MEDIUM
                );
                break;

            case Event::UPDATE_ALIASES:
                $this->createEvent(SystemEvent::TYPE_UPDATE_ALIASES, '', SystemEvent::PRIORITY_HIGH);
                break;

            default:
                break;
        }
    }

    /**
     * Create a new event, store it in the db and send notifications
     * @return SystemEvent or null
     */
    public function createEvent($type, $parameters, $priority, $owner = SystemEvent::OWNER_ROOT, $klass = null)
    {
        SystemEventInstrumentation::increment(SystemEvent::STATUS_NEW);
        if ($id = $this->dao->store($type, $parameters, $priority, SystemEvent::STATUS_NEW, $_SERVER['REQUEST_TIME'], $owner)) {
            if ($klass) {
                $sysevent = $this->instanciateSystemEventOnCreateByClass($id, $type, $owner, $parameters, $priority, $klass);
            } else {
                $sysevent = $this->instanciateSystemEventOnCreate($id, $type, $owner, $parameters, $priority);
            }
            $sysevent->notify($this->_getFollowersDao());
            return $sysevent;
        }
        return null;
    }

    private function instanciateSystemEventOnCreateByClass($id, $type, $owner, $parameters, $priority, $klass)
    {
        return $this->instanciateSystemEvent($klass, $id, $type, $owner, $parameters, $priority, SystemEvent::STATUS_NEW, $_SERVER['REQUEST_TIME'], null, null, null);
    }

    private function instanciateSystemEventOnCreate($id, $type, $owner, $parameters, $priority)
    {
        return $this->instanciateSystemEventByType($id, $type, $owner, $parameters, $priority, SystemEvent::STATUS_NEW, $_SERVER['REQUEST_TIME'], null, null, null);
    }

    private function instanciateSystemEventByType($id, $type, $owner, $parameters, $priority, $status, $create_time, $process_time, $end_time, $log)
    {
        $system_event = $this->instanciateSystemEvent($type, $id, $type, $owner, $parameters, $priority, $status, $create_time, $process_time, $end_time, $log);
        if ($system_event === null) {
            $klass        = $this->getClassForType($type);
            $system_event = $this->instanciateSystemEvent($klass, $id, $type, $owner, $parameters, $priority, $status, $create_time, $process_time, $end_time, $log);
        }
        return $system_event;
    }

    private function getClassForType($type)
    {
        switch ($type) {
            case SystemEvent::TYPE_MASSMAIL:
                return \Tuleap\SystemEvent\Massmail::class;
            case SystemEvent::TYPE_PROJECT_SVN_AUTHENTICATION_CACHE_REFRESH:
                return SystemEventSVNAuthenticationCacheRefresh::class;
            case SystemEvent::TYPE_PROJECT_ACTIVE:
                return \Tuleap\SystemEvent\SystemEventProjectActive::class;
            case SystemEvent::TYPE_USER_ACTIVE_STATUS_CHANGE:
                return SystemEventUserActiveStatusChange::class;
            case SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES:
                return UpdateProjectAccessFileSystemEvent::class;
            default:
                return 'SystemEvent_' . $type;
        }
    }

    private function instanciateSystemEvent($klass, $id, $type, $owner, $parameters, $priority, $status, $create_time, $process_time, $end_time, $log)
    {
        if (class_exists($klass)) {
            return new $klass(
                $id,
                $type,
                $owner,
                $parameters,
                $priority,
                $status,
                $create_time,
                $process_time,
                $end_time,
                $log
            );
        }
        return null;
    }

    /**
     * Concat parameters as $params['key1'] . SEPARATOR . $params['key3'] ...
     * @param array $params
     * @param array $keys array('key1', 'key3')
     */
    public function concatParameters($params, $keys)
    {
        $concat = [];
        foreach ($keys as $key) {
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
    public function getInstanceFromRow($row)
    {
        $em           = EventManager::instance();
        $sysevent     = null;
        $klass        = null;
        $klass_params = null;
        switch ($row['type']) {
            case SystemEvent::TYPE_USER_ACTIVE_STATUS_CHANGE:
                $klass        = SystemEventUserActiveStatusChange::class;
                $user_manager = UserManager::instance();
                $klass_params = [
                    $user_manager,
                    new UserGroupDao(),
                    new UserRemover(
                        ProjectManager::instance(),
                        EventManager::instance(),
                        new ArtifactTypeFactory(false),
                        new UserRemoverDao(),
                        $user_manager,
                        new ProjectHistoryDao(),
                        new UGroupManager(),
                        new UserPermissionsDao(),
                    ),
                ];
                break;
            case SystemEvent::TYPE_SYSTEM_CHECK:
            case SystemEvent::TYPE_EDIT_SSH_KEYS:
            case SystemEvent::TYPE_PROJECT_CREATE:
            case SystemEvent::TYPE_PROJECT_RENAME:
            case SystemEvent::TYPE_MEMBERSHIP_CREATE:
            case SystemEvent::TYPE_MEMBERSHIP_DELETE:
            case SystemEvent::TYPE_USER_DELETE:
            case SystemEvent::TYPE_USER_RENAME:
            case SystemEvent::TYPE_MAILING_LIST_CREATE:
            case SystemEvent::TYPE_MAILING_LIST_DELETE:
            case SystemEvent::TYPE_CVS_IS_PRIVATE:
            case SystemEvent::TYPE_SERVICE_USAGE_SWITCH:
            case SystemEvent::TYPE_ROOT_DAILY:
            case SystemEvent::TYPE_COMPUTE_MD5SUM:
            case SystemEvent::TYPE_MASSMAIL:
                $klass = $this->getClassForType($row['type']);
                break;
            case SystemEvent::TYPE_UGROUP_MODIFY:
                $klass        = $this->getClassForType($row['type']);
                $klass_params = [new UpdateProjectAccessFilesScheduler($this)];
                break;
            case SystemEvent::TYPE_SVN_AUTH_CACHE_CHANGE:
                $klass        = $this->getClassForType($row['type']);
                $klass_params = [Backend::instance(Backend::SVN)];
                break;
            case SystemEvent::TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES:
                $klass        = $this->getClassForType($row['type']);
                $klass_params = [ProjectManager::instance(), EventManager::instance()];
                break;
            case SystemEvent::TYPE_PROJECT_IS_PRIVATE:
                $klass          = $this->getClassForType($row['type']);
                $ugroup_manager = new UGroupManager();
                $klass_params   = [
                    new UserRemover(
                        ProjectManager::instance(),
                        EventManager::instance(),
                        new ArtifactTypeFactory(false),
                        new UserRemoverDao(),
                        UserManager::instance(),
                        new ProjectHistoryDao(),
                        $ugroup_manager,
                        new UserPermissionsDao(),
                    ),
                    $ugroup_manager,
                ];
                break;
            case SystemEvent::TYPE_PROJECT_ACTIVE:
            case SystemEvent::TYPE_PROJECT_DELETE:
            case SystemEvent::TYPE_PROJECT_SVN_AUTHENTICATION_CACHE_REFRESH:
                $klass        = $this->getClassForType($row['type']);
                $klass_params = [$this->getSVNAuthenticationCacheInvalidator()];
                break;

            default:
                $em->processEvent(Event::GET_SYSTEM_EVENT_CLASS, ['type' => $row['type'], 'class' => &$klass, 'dependencies' => &$klass_params]);
                break;
        }
        $sysevent = $this->instanciateSystemEventByType(
            $row['id'],
            class_exists($klass) ? $klass : $row['type'],
            $row['owner'],
            $row['parameters'],
            $row['priority'],
            $row['status'],
            $row['create_date'],
            $row['process_date'],
            $row['end_date'],
            $row['log']
        );
        if ($sysevent && ! empty($klass_params)) {
            call_user_func_array([$sysevent, 'injectDependencies'], $klass_params);
        }
        return $sysevent;
    }

    /**
     * @return SVNAuthenticationCacheInvalidator
     */
    private function getSVNAuthenticationCacheInvalidator()
    {
        $redis_client = null;
        if (Redis\ClientFactory::canClientBeBuiltFromForgeConfig()) {
            $redis_client = Redis\ClientFactory::fromForgeConfig();
        }
        return new SVNAuthenticationCacheInvalidator(new ApacheServiceControl(new ServiceControl(), new ProcessFactory()), $redis_client);
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $reflect = new ReflectionClass(SystemEvent::class);
        $consts  = $reflect->getConstants();
        array_walk($consts, [$this, 'filterConstants']);
        $types = array_filter($consts);
        EventManager::instance()->processEvent(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE, ['types' => &$types]);

        return $types;
    }

    public function getTypesForQueue($queue)
    {
        switch ($queue) {
            case SystemEvent::DEFAULT_QUEUE:
            case SystemEvent::APP_OWNER_QUEUE:
                return $this->getTypes();
            default:
                $types = [];
                EventManager::instance()->processEvent(
                    Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE,
                    [
                        'queue' => $queue,
                        'types' => &$types,
                    ]
                );

                return $types;
        }
    }

    protected function filterConstants(&$item, $key)
    {
        if (strpos($key, 'TYPE_') !== 0) {
            $item = null;
        }
    }

    /**
     * Compute a html table to display the status of the last 10 events,
     * displayed in my personal page as a widget for site administrators
     *
     * @return string html
     */
    public function fetchLastTenEventsStatusWidget()
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html  = '';
        $html .= '<table class="table tlp-table">';
        $html .= '<tbody>';

        $filter_status = [
            SystemEvent::STATUS_NEW,
            SystemEvent::STATUS_RUNNING,
            SystemEvent::STATUS_DONE,
            SystemEvent::STATUS_WARNING,
            SystemEvent::STATUS_ERROR,
        ];

        $filter_type = $this->getTypesForQueue(SystemEvent::DEFAULT_QUEUE);

        $offset = 0;
        $limit  = 10;

        $events = $this->dao->searchLastEvents($offset, $limit, $filter_status, $filter_type);
        foreach ($events as $row) {
            if ($sysevent = $this->getInstanceFromRow($row)) {
                $html .= '<tr>';

                //id
                $html .= '<td>' . $sysevent->getId() . '</td>';

                //name of the event
                $html .= '<td>' . $sysevent->getType() . '</td>';

                $html .= '<td>' . $sysevent->getOwner() . '</td>';

                //status
                $html .= '<td class="system_event_status_' . $row['status'] . ' system-event-status-' . strtolower($row['status']) . '"';
                if ($sysevent->getLog()) {
                    $html .= ' title="' . $purifier->purify($sysevent->getLog(), CODENDI_PURIFIER_CONVERT_HTML) . '" ';
                }
                $html .= '>';
                $html .= $sysevent->getStatus();
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * NOTE: The first Parameter has in fact NOTHING to do with the order of the
     * arguments this method takes.
     *
     * FYI: Each system event is created with a certain amount of parameters.
     * These parameters are seperated by SystemEvent::PARAMETER_SEPARATOR.
     * This creates a string of concatenated parameters for each system event.
     *
     * This method checks all events of type $event_type to see if the first
     * element in the concatenated string matches the value $parameter. If there
     * is a match, it returns true.
     *
     * @param string $event_type
     * @param string|int|bool $parameter
     * @return bool
     */
    public function isThereAnEventAlreadyOnGoingMatchingFirstParameter($event_type, $parameter)
    {
        $dar = $this->_getDao()->searchWithParam(
            'head',
            $parameter,
            [$event_type],
            [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]
        );
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        return false;
    }

    public function areThereMultipleEventsQueuedMatchingFirstParameter($event_type, $parameter)
    {
        $dar = $this->_getDao()->searchWithParam(
            'all',
            $parameter,
            [$event_type],
            [SystemEvent::STATUS_NEW]
        );

        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param type $event_type
     * @param type $parameter
     * @return bool
     */
    public function isThereAnEventAlreadyOnGoingMatchingParameter($event_type, $parameter)
    {
        $dar = $this->_getDao()->searchWithParam(
            null,
            $parameter,
            [$event_type],
            [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]
        );
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isThereAnEventAlreadyOnGoing($event_type)
    {
        $dar = $this->_getDao()->searchWithTypeAndStatus(
            [$event_type],
            [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]
        );

        return $dar && ! $dar->isError() && $dar->rowCount() > 0;
    }

    /**
     * Return true if there is no pending rename event of this user, otherwise false
     *
     * @param PFUser $user
     * @return bool
     */
    public function canRenameUser($user)
    {
        return ! $this->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent::TYPE_USER_RENAME, $user->getId());
    }

    /**
     * Return true if there is no pending rename event of this project, otherwise false
     *
     * @param Project $project
     * @return bool
     */
    public function canRenameProject($project)
    {
        return ! $this->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent::TYPE_PROJECT_RENAME, $project->getId());
    }

    /**
     * Return true if there is no pending rename user event on this new name
     * @param string $newName
     * @return bool
     */
    public function isUserNameAvailable($newName)
    {
        $dar = $this->_getDao()->searchWithParam('tail', $newName, [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]);
        if ($dar && ! $dar->isError() && $dar->rowCount() == 0) {
            return true;
        }
        return false;
    }

    /**
     * Return true if there is no pending rename project event on this new name
     * @param string $newName
     * @return bool
     */
    public function isProjectNameAvailable($newName)
    {
        $dar = $this->_getDao()->searchWithParam('tail', $newName, [SystemEvent::TYPE_PROJECT_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING]);
        if ($dar && ! $dar->isError() && $dar->rowCount() == 0) {
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
    public function replay($id)
    {
        return $this->_getDao()->resetStatus($id, SystemEvent::STATUS_NEW);
    }
}
