<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2015 - Present. All rights reserved
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Notification\Notification;

class Docman_NotificationsManager
{
    public const MESSAGE_MODIFIED        = 'modified';
    public const MESSAGE_NEWVERSION      = 'new_version';
    public const MESSAGE_WIKI_NEWVERSION = 'new_wiki_version';

    public $_listeners;
    public $_feedback;
    public $_item_factory;
    /** @var array */
    private $notifications;

    /**
     * @var Project
     */
    public $project;

    public $_group_name;

    /**
     * @var MailBuilder
     */
    private $mail_builder;
    /**
     * @var UsersToNotifyDao
     */
    private $users_to_notify_dao;

    /**
     * @var UsersRetriever
     */
    private $users_retriever;

    /**
     * @var UGroupsRetriever
     */
    private $ugroups_retriever;
    /**
     * @var NotifiedPeopleRetriever
     */
    protected $notified_people_retriever;

    /**
     * @var UsersUpdater
     */
    private $users_updater;

    /**
     * @var UgroupsUpdater
     */
    private $ugroups_updater;
    /**
     * @var ILinkUrlProvider
     */
    protected $url_provider;

    public function __construct(
        Project $project,
        ILinkUrlProvider $url_provider,
        $feedback,
        MailBuilder $mail_builder,
        UsersToNotifyDao $users_to_notify_dao,
        UsersRetriever $users_retriever,
        UGroupsRetriever $ugroups_retriever,
        NotifiedPeopleRetriever $notified_people_retriever,
        UsersUpdater $users_updater,
        UgroupsUpdater $ugroups_updater,
    ) {
        $this->project       = $project;
        $this->_listeners    = [];
        $this->_feedback     = $feedback;
        $this->_item_factory = $this->_getItemFactory();
        $this->notifications = [];
        $this->mail_builder  = $mail_builder;
        if ($project && ! $project->isError()) {
            $this->_group_name = $project->getPublicName();
        }
        $this->users_to_notify_dao       = $users_to_notify_dao;
        $this->users_retriever           = $users_retriever;
        $this->ugroups_retriever         = $ugroups_retriever;
        $this->notified_people_retriever = $notified_people_retriever;
        $this->users_updater             = $users_updater;
        $this->ugroups_updater           = $ugroups_updater;
        $this->url_provider              = $url_provider;
    }

    public function _getItemFactory()
    {
        return new Docman_ItemFactory();
    }

    public function _getUserManager()
    {
        return UserManager::instance();
    }

    public function _getPermissionsManager()
    {
        return Docman_PermissionsManager::instance($this->project->getID());
    }

    public function _getDocmanPath()
    {
        return new Docman_Path();
    }

    public function somethingHappen($event, $params)
    {
        $um             = $this->_getUserManager();
        $params['path'] = $this->_getDocmanPath();
        $users          = $this->notified_people_retriever->getNotifiedUsers(
            $this->project,
            $this->_getListeningUsersItemId($params)
        );
        if ($users) {
            while ($users->valid()) {
                $u    = $users->current();
                $user = $um->getUserById($u['user_id']);
                if ($user->isActive() || $user->isRestricted()) {
                    $dpm = $this->_getPermissionsManager();
                    if ($dpm->userCanAccess($user, $params['item']->getId()) && $dpm->userCanAccess($user, $u['item_id'])) {
                        $this->_buildMessage($event, $params, $user);
                    }
                }
                $users->next();
            }
        }
    }

    public function _getListeningUsersItemId($params)
    {
        return $params['item']->getId();
    }

    public function sendNotifications($event, $params)
    {
        $success = true;
        foreach ($this->notifications as $notification) {
            $success &= $this->mail_builder->buildAndSendEmail($this->project, $notification, new MailEnhancer());
        }
        if (! $success) {
            $this->_feedback->log('warning', 'Error when sending some notifications.');
        }
    }

    /* protected */ public function _getType()
    {
        return PLUGIN_DOCMAN_NOTIFICATION;
    }

   /**
    * Returns the list of users monitoring the given item with an array associated to the item the user actually monitors:
    * getListeningUsers(item(10))
    * =>
    *  array(101 => item(10) // The user is monitoring the item(10) directly
    *        102 => item(20) // The user is monitoring item(10) through item(20) "sub-hierarchy"
    *  )
    *
    * @param Docman_Item $item  Item which listenners will be retrieved
    *
    * @return Array
    */
    public function getListeningUsers(Docman_Item $item)
    {
        $users = [];
        return $this->users_retriever->getListeningUsers($item, $users, PLUGIN_DOCMAN_NOTIFICATION);
    }

    public function getListeningUGroups(Docman_Item $item)
    {
        $ugroups = [];
        return $this->ugroups_retriever->getListeningUGroups($item, $ugroups, PLUGIN_DOCMAN_NOTIFICATION);
    }

    public function _buildMessage($event, $params, $user)
    {
        $type = '';
        switch ($event) {
            case 'plugin_docman_event_edit':
            case 'plugin_docman_event_metadata_update':
                $type = self::MESSAGE_MODIFIED;
                break;
            case 'plugin_docman_event_new_version':
                $type = self::MESSAGE_NEWVERSION;
                break;
            case 'plugin_docman_event_wikipage_update':
                $type = self::MESSAGE_WIKI_NEWVERSION;
                break;
            default:
                break;
        }
        $this->_addMessage(
            $user,
            $params['item']->getTitle(),
            $this->_getMessageForUser(
                $params['user'],
                $type,
                $params
            ),
            $this->getMessageLink($type, $params)
        );
    }

    protected function _addMessage(PFUser $to, $subject, $msg, $link)
    {
        if (! isset($this->notifications[$msg])) {
            $subject = '[' . $this->_group_name . ' - Documents] ' . $subject;

            $this->notifications[$msg] = new Notification(
                [],
                $subject,
                Codendi_HTMLPurifier::instance()->purify($msg, CODENDI_PURIFIER_BASIC),
                $msg,
                $link,
                'Documents'
            );
        }
        $this->notifications[$msg]->addEmail($to->getEmail());
    }

    protected function getMessageLink($type, $params)
    {
        if ($this->project->getTruncatedEmailsUsage()) {
            return $this->url_provider->getHistoryUrl($params['item']);
        }

        switch ($type) {
            case self::MESSAGE_MODIFIED:
            case self::MESSAGE_NEWVERSION:
                $link = $this->url_provider->getDetailsLinkUrl($params['item']);
                break;
            case self::MESSAGE_WIKI_NEWVERSION:
                $link = $params['url'];
                break;
            default:
                $link = $this->url_provider->getPluginLinkUrl();
                break;
        }
        return $link;
    }

    /**
     * Given an item monitored by user through "sub-hierarchy", retrieve the monitored parent.
     * @see getListeningUsers
     *
     * @param $user User monitoring the item
     * @param $item Item which parent is monitored
     *
     * @return Docman_Item
     */
    public function _getMonitoredItemForUser($user, $item)
    {
        $listeners = $this->getListeningUsers($item);
        foreach ($listeners as $userId => $item) {
            if ($user->getId() == $userId) {
                return $item;
            }
        }
        return $item;
    }

    public function _getMessageForUser($user, $message_type, $params)
    {
        $msg = '';
        switch ($message_type) {
            case self::MESSAGE_MODIFIED:
            case self::MESSAGE_NEWVERSION:
                $msg .=
                    sprintf(
                        dgettext('tuleap-docman', '%s has been modified by %s.'),
                        $params['path']->get($params['item']),
                        $user->getRealName()
                    ) . "\n";
                $msg .= $this->getMessageLink($message_type, $params) . "\n";
                break;
            case self::MESSAGE_WIKI_NEWVERSION:
                $msg .= sprintf(
                    dgettext('tuleap-docman', "New version of %s wiki page was created by %s."),
                    $params['wiki_page'],
                    $user->getRealName()
                ) . "\n";
                $msg .= $this->getMessageLink($message_type, $params) . "\n";
                break;
            default:
                $msg .= dgettext('tuleap-docman', 'Something happen!');
                break;
        }
        $monitoredItem = $this->_getMonitoredItemForUser($user, $params['item']);
        $msg          .= $this->getMonitoringInformation($monitoredItem);

        return $msg;
    }

    /**
     * Retrieve all monitored Items (folders & documents) of a given project
     * and if provided by user
     *
     * @param $groupId
     * @param $userId
     *
     * @return LegacyDataAccessResultInterface|false
     */
    public function listAllMonitoredItems($groupId, $userId = null)
    {
        return $this->users_to_notify_dao->searchDocmanMonitoredItems($groupId, $userId);
    }

    public function add($user_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->users_to_notify_dao->create($user_id, $item_id, $type);
    }

    public function addUser($user_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->users_updater->create($user_id, $item_id, $type);
    }

    public function addUgroup($ugroup_id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }
        return $this->ugroups_updater->create($ugroup_id, $item_id, $type);
    }

    public function removeUser($id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }

        return $this->users_updater->delete($id, $item_id, $type);
    }

    public function removeUgroup($id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }

        return $this->ugroups_updater->delete($id, $item_id, $type);
    }

    public function userExists($id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }

        return $this->doesNotificationConcernAUser($id, $item_id, $type) === true;
    }

    public function ugroupExists($id, $item_id, $type = null)
    {
        if ($type === null) {
            $type = $this->_getType();
        }

        return $this->doesNotificationConcernAUGroup($id, $item_id, $type) === true;
    }

    private function doesNotificationConcernAUser($user_id, $item_id, $type)
    {
        return $this->users_retriever->doesNotificationExistByUserAndItemId($user_id, $item_id, $type);
    }

    private function doesNotificationConcernAUGroup($user_id, $item_id, $type)
    {
        return $this->ugroups_retriever->doesNotificationExistByUGroupAndItemId($user_id, $item_id, $type);
    }

    protected function getMonitoringInformation(Docman_Item $monitored_item): string
    {
        $message  = "\n\n--------------------------------------------------------------------\n";
        $message .= dgettext(
            'tuleap-docman',
            "You are receiving this message because you are monitoring this item."
        );
        $message .= "\n";
        $message .= dgettext(
            'tuleap-docman',
            "To stop monitoring, please visit:"
        );
        $message .= "\n";
        $message .= $this->getUrlProvider()->getNotificationLinkUrl($monitored_item);

        return $message;
    }

    /**
     * protected for testing purpose
     * @return ILinkUrlProvider
     */
    protected function getUrlProvider()
    {
        return $this->url_provider;
    }
}
