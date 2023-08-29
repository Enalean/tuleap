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

/**
 * System Event class
 *
 */
abstract class SystemEvent
{
    protected $id;
    protected $type;
    protected $owner;
    protected $parameters;
    protected $priority;
    protected $status;
    protected $create_date;
    protected $process_date;
    protected $end_date;
    protected $log;

    // Define event types
    public const TYPE_SYSTEM_CHECK                             = "SYSTEM_CHECK";
    public const TYPE_EDIT_SSH_KEYS                            = "EDIT_SSH_KEYS";
    public const TYPE_PROJECT_CREATE                           = "PROJECT_CREATE";
    public const TYPE_PROJECT_ACTIVE                           = "PROJECT_ACTIVE";
    public const TYPE_PROJECT_DELETE                           = "PROJECT_DELETE";
    public const TYPE_PROJECT_SVN_AUTHENTICATION_CACHE_REFRESH = "PROJECT_SVN_AUTHENTICATION_CACHE_REFRESH";
    public const TYPE_PROJECT_RENAME                           = "PROJECT_RENAME";
    public const TYPE_UGROUP_MODIFY                            = "UGROUP_MODIFY";
    public const TYPE_USER_ACTIVE_STATUS_CHANGE                = "ACTIVE_USER_STATUS_CHANGE";
    public const TYPE_USER_MODIFY                              = "USER_MODIFY";
    public const TYPE_USER_RENAME                              = "USER_RENAME";
    public const TYPE_MEMBERSHIP_CREATE                        = "MEMBERSHIP_CREATE";
    public const TYPE_MEMBERSHIP_DELETE                        = "MEMBERSHIP_DELETE";
    public const TYPE_MEMBERSHIP_MODIFY                        = "MEMBERSHIP_MODIFY";
    public const TYPE_PROJECT_IS_PRIVATE                       = "PROJECT_IS_PRIVATE";
    public const TYPE_SERVICE_USAGE_SWITCH                     = "SERVICE_USAGE_SWITCH";
    public const TYPE_ROOT_DAILY                               = "ROOT_DAILY";
    public const TYPE_COMPUTE_MD5SUM                           = "COMPUTE_MD5SUM";
    public const TYPE_MASSMAIL                                 = "MASSMAIL";
    public const TYPE_SVN_AUTH_CACHE_CHANGE                    = "SVN_AUTH_CACHE_CHANGE";
    public const TYPE_MOVE_FRS_FILE                            = "MOVE_FRS_FILE";
    public const TYPE_UPDATE_ALIASES                           = "UPDATE_ALIASES";
    public const TYPE_SVN_UPDATE_PROJECT_ACCESS_FILES          = 'UPDATE_SVN_ACCESS_FILE';

    // Define status value (in sync with DB enum)
    public const STATUS_NONE    = "NONE";
    public const STATUS_NEW     = "NEW";
    public const STATUS_RUNNING = "RUNNING";
    public const STATUS_DONE    = "DONE";
    public const STATUS_WARNING = "WARNING";
    public const STATUS_ERROR   = "ERROR";

    public const ALL_STATUS = [
        self::STATUS_NONE,
        self::STATUS_NEW,
        self::STATUS_RUNNING,
        self::STATUS_DONE,
        self::STATUS_WARNING,
        self::STATUS_ERROR,
    ];

    //Priority of the event
    public const PRIORITY_HIGH   = 1;
    public const PRIORITY_MEDIUM = 2;
    public const PRIORITY_LOW    = 3;

    public const PARAMETER_SEPARATOR        = '::';
    public const PARAMETER_SEPARATOR_ESCAPE = '\:\:';

    // Who should execute the event
    public const OWNER_ROOT = 'root';
    public const OWNER_APP  = 'app';

    public const APP_OWNER_QUEUE = 'owner';
    public const DEFAULT_QUEUE   = 'default';

    /**
     * Constructor
     * @param int $id The id of the event
     * @param string $type SystemEvent type (const defined in this class)
     * @param string $parameters Event Parameter (e.g. group_id if event type is PROJECT_CREATE)
     * @param int $priority Event priority (PRIORITY_HIGH | PRIORITY_MEDIUM | PRIORITY_LOW)
     * @param string $status Event status (STATUS_NEW | STATUS_RUNNING | STATUS_DONE | STATUS_WARNING | STATUS_ERROR)
     * @param string $create_date
     * @param string $process_date
     * @param string $end_date
     * @param string $log
     */
    public function __construct($id, $type, $owner, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log)
    {
        $this->id           = $id;
        $this->type         = $type;
        $this->owner        = $owner;
        $this->parameters   = $parameters;
        $this->priority     = $priority;
        $this->status       = $status;
        $this->create_date  = is_numeric($create_date) ? date('Y-m-d H:i:s', $create_date) : $create_date;
        $this->process_date = is_numeric($process_date) ? date('Y-m-d H:i:s', $process_date) : $process_date;
        $this->end_date     = is_numeric($end_date) ? date('Y-m-d H:i:s', $end_date) : $end_date;
        $this->log          = $log;
    }

    // Getters

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    abstract public function verbalizeParameters($with_link);

    /**
     * verbalize a user id.
     *
     * @param int $user_id The user id
     * @param bool $with_link true if you want links to entities. The returned string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeUserId($user_id, $with_link)
    {
        $txt = '#' . $user_id;
        if ($with_link) {
            $txt = '<a href="/admin/usergroup.php?user_id=' . $user_id . '">' . $txt . '</a>';
        }
        return $txt;
    }

    /**
     * verbalize a project id.
     *
     * @param int $group_id The project id
     * @param bool $with_link true if you want links to entities. The returned string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeProjectId($group_id, $with_link)
    {
        $txt = '#' . $group_id;
        if ($with_link) {
            $txt = '<a href="/admin/groupedit.php?group_id=' . $group_id . '">' . $txt . '</a>';
        }
        return $txt;
    }

    public function getParametersAsArray()
    {
        return explode(self::PARAMETER_SEPARATOR, $this->parameters ?? '');
    }

    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @psalm-return value-of<self::ALL_STATUS>
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLog(): string
    {
        return $this->log ?? '';
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setLog(string $log): void
    {
        $this->log = $log;
    }

    public function setParameters($params)
    {
        $this->parameters = $params;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function getProcessDate()
    {
        return $this->process_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getTimeTaken()
    {
        if ($this->end_date === '0000-00-00 00:00:00') {
            return '';
        }

        $start = DateTime::createFromFormat('Y-m-d H:i:s', $this->process_date);
        $end   = DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);

        $duration = $end->diff($start);

        if ($duration->y) {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_years');
        } elseif ($duration->m) {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_months');
        } elseif ($duration->d) {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_days');
        } elseif ($duration->h) {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_hours');
        } elseif ($duration->i) {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_minutes');
        } else {
            $format = $GLOBALS['Language']->getText('admin_system_events', 'duration_seconds');
        }

        return $duration->format($format);
    }

    public function setProcessDate($process_date)
    {
        $this->process_date = is_numeric($process_date) ? date('Y-m-d H:i:s', $process_date) : $process_date;
    }

    public function setEndDate($end_date)
    {
        $this->end_date = is_numeric($end_date) ? date('Y-m-d H:i:s', $end_date) : $end_date;
    }

    /**
     * Checks if the given value represents integer
     * is_int() won't work on string containing integers...
     */
    public function int_ok($val)
    {
        return ((string) $val) === ((string) (int) $val);
    }

    /**
     * A few functions to parse the parameters string
     */
    public function getIdFromParam()
    {
        if ($this->int_ok($this->parameters)) {
            return $this->parameters;
        } else {
            return 0;
        }
    }

    public function getParameter($index)
    {
        $params = $this->getParametersAsArray();
        return isset($params[$index]) && $params[$index] !== '' ? $params[$index] : null;
    }

    public function getRequiredParameter($index)
    {
        $param = $this->getParameter($index);
        if ($param === null) {
            throw new SystemEventMissingParameterException('Missing parameter n°' . (int) $index);
        }
        return $param;
    }

    /**
     * Error functions
     */
    public function setErrorBadParam()
    {
        $this->error("Bad parameter for event " . $this->getType() . ": " . $this->getParameters());
        return 0;
    }

    /**
     * Process stored event
     * Virtual method redeclared in children
     */
    public function process()
    {
        return null;
    }

    /**
     * This function allows one to call all listeners (e.g. plugins) of an event related to the current processed system event
     * @param string $eventName
     */
    protected function callSystemEventListeners($eventName)
    {
        EventManager::instance()->processEvent($eventName, $this->getParametersAsArray());
    }

    public function logException(Exception $exception)
    {
        $this->error($exception->getMessage());
    }

    /**
     * Private. Use error() | done() | ... instead
     * @param string $status the status
     * @param string $msg the message to log
     */
    private function logStatus($status, $msg)
    {
        $this->setStatus($status);
        $this->setLog($msg);
    }

    /**
     * Set the status of the event to STATUS_ERROR
     * and log the msg
     * @param string $msg the message to log
     */
    protected function error($msg)
    {
        $this->logStatus(self::STATUS_ERROR, $msg);
    }

    /**
     * Set the status of the event to STATUS_DONE
     * and log the msg
     * @param string $msg the message to log. default is 'OK'
     */
    protected function done($msg = 'OK')
    {
        $this->logStatus(self::STATUS_DONE, $msg);
    }

    /**
     * Set the status of the event to STATUS_WARNING
     * and log the msg
     * @param string $msg the message to log.
     */
    protected function warning($msg)
    {
        $this->logStatus(self::STATUS_WARNING, $msg);
    }

    /**
     * Initialize a project from the given $group_id
     * @param int $group_id the id of the project
     * @return Project
     */
    protected function getProject($group_id)
    {
        if (! $group_id) {
            return $this->setErrorBadParam();
        }

        $project = ProjectManager::instance()->getProject($group_id);

        if (! $project) {
            $this->error("Could not create/initialize project object");
        }

        return $project;
    }

    /**
     * Initialize a user from the given $user_id
     * @param int $user_id the id of the User
     * @return PFUser
     */
    protected function getUser($user_id)
    {
        if (! $user_id) {
            return $this->setErrorBadParam();
        }

        $user = UserManager::instance()->getUserById($user_id);

        if (! $user) {
            $this->error("Could not create/initialize user object");
        }

        return $user;
    }

    /**
     * Wrapper for event manager
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Notify people that listen to the status of the event
     */
    public function notify(?SystemEventsFollowersDao $dao = null)
    {
        if (is_null($dao)) {
            $dao = new SystemEventsFollowersDao(CodendiDataAccess::instance());
        }
        $listeners = [];
        foreach ($dao->searchByType($this->getStatus()) as $row) {
            $listeners = array_merge($listeners, explode(',', $row['emails']));
        }
        if (count($listeners)) {
            $listeners = array_unique($listeners);
            $m         = new Codendi_Mail();
            $m->setFrom(ForgeConfig::get('sys_noreply'));
            $m->setTo(implode(',', $listeners));
            $m->setSubject('[' . $this->getstatus() . '] ' . $this->getType());
            $m->setBodyText("
Event:        #{$this->getId()}
Type:         {$this->getType()}
Parameters:   {$this->verbalizeParameters(false)}
Priority:     {$this->getPriority()}
Status:       {$this->getStatus()}
Log:          {$this->getLog()}
Create Date:  {$this->getCreateDate()}
Process Date: {$this->getProcessDate()}
End Date:     {$this->getEndDate()}
---------------
<" . \Tuleap\ServerHostname::HTTPSUrl() . "/admin/system_events/>
");
            $m->send();
        }
    }

    /**
     * Wrapper for Backend
     *
     * @param String $type Backend type
     *
     * @return Backend
     */
    protected function getBackend($type)
    {
        return Backend::instance($type);
    }

    /**
     * @param mixed $data The data to encode (string, array, int, ...)
     *
     * @return string suitable to be enclosed as parameter
     */
    public static function encode($data)
    {
        return str_replace(self::PARAMETER_SEPARATOR, self::PARAMETER_SEPARATOR_ESCAPE, json_encode(['data' => $data]));
    }

    /**
     * @param string $string encoded string
     *
     * @return mixed data
     */
    public static function decode($string)
    {
        $decode = json_decode(str_replace(self::PARAMETER_SEPARATOR_ESCAPE, self::PARAMETER_SEPARATOR, $string), true);
        if (isset($decode['data'])) {
            return $decode['data'];
        }
    }
}
