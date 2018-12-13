<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Docman_ItemFactory;
use Docman_NotificationsManager;
use Docman_NotificationsManager_Add;
use Docman_NotificationsManager_Delete;
use Docman_NotificationsManager_Move;
use Docman_NotificationsManager_Subscribers;
use EventManager;
use MailBuilder;
use Project;
use TemplateRendererFactory;
use Tuleap\Docman\ResponseFeedbackWrapper;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;
use URLVerification;
use UserManager;

class NotificationBuilders
{
    /**
     * @var ResponseFeedbackWrapper
     */
    private $feedback;
    /**
     * @var Project
     */
    private $project;
    private $default_url;

    public function __construct(ResponseFeedbackWrapper $feedback, Project $project, $default_url)
    {
        $this->feedback    = $feedback;
        $this->project     = $project;
        $this->default_url = $default_url;
    }

    public function buildNotificationManager()
    {
        return new Docman_NotificationsManager(
            $this->project,
            get_server_url() . $this->default_url,
            $this->feedback,
            $this->getMailBuilder(),
            $this->getUsersToNotifyDao(),
            $this->getUsersRetriever(),
            $this->getUGroupsRetriever(),
            $this->getNotifiedPeopleRetriever(),
            $this->getUserUpdater(),
            $this->getUGroupUpdater()
        );
    }

    public function buildNotificationManagerAdd()
    {
        return new Docman_NotificationsManager_Add(
            $this->project,
            get_server_url() . $this->default_url,
            $this->feedback,
            $this->getMailBuilder(),
            $this->getUsersToNotifyDao(),
            $this->getUsersRetriever(),
            $this->getUGroupsRetriever(),
            $this->getNotifiedPeopleRetriever(),
            $this->getUserUpdater(),
            $this->getUGroupUpdater()
        );
    }

    public function buildNotificationManagerDelete()
    {
        return new Docman_NotificationsManager_Delete(
            $this->project,
            get_server_url() . $this->default_url,
            $this->feedback,
            $this->getMailBuilder(),
            $this->getUsersToNotifyDao(),
            $this->getUsersRetriever(),
            $this->getUGroupsRetriever(),
            $this->getNotifiedPeopleRetriever(),
            $this->getUserUpdater(),
            $this->getUGroupUpdater()
        );
    }

    public function buildNotificationManagerMove()
    {
        return new Docman_NotificationsManager_Move(
            $this->project,
            get_server_url() . $this->default_url,
            $this->feedback,
            $this->getMailBuilder(),
            $this->getUsersToNotifyDao(),
            $this->getUsersRetriever(),
            $this->getUGroupsRetriever(),
            $this->getNotifiedPeopleRetriever(),
            $this->getUserUpdater(),
            $this->getUGroupUpdater()
        );
    }

    public function buildNotificationManagerSubsribers()
    {
        return new Docman_NotificationsManager_Subscribers(
            $this->project,
            get_server_url() . $this->default_url,
            $this->feedback,
            $this->getMailBuilder(),
            $this->getUsersToNotifyDao(),
            $this->getUsersRetriever(),
            $this->getUGroupsRetriever(),
            $this->getNotifiedPeopleRetriever(),
            $this->getUserUpdater(),
            $this->getUGroupUpdater()
        );
    }

    private function getMailBuilder()
    {
        return new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(UserManager::instance(), new URLVerification(), new MailLogger())
        );
    }

    /**
     * @return UsersRetriever
     */
    private function getUsersRetriever()
    {
        return new UsersRetriever(
            $this->getUsersToNotifyDao(),
            new Docman_ItemFactory()
        );
    }

    /**
     * @return UGroupsRetriever
     */
    private function getUGroupsRetriever()
    {
        return new UGroupsRetriever($this->getUgroupsToNotifyDao(), $this->getItemFactory());
    }

    /**
     * @return UGroupManager
     */
    private function getUGroupManager()
    {
        return new UGroupManager(
            new UGroupDao(),
            new EventManager(),
            new UGroupUserDao()
        );
    }

    private function getNotifiedPeopleRetriever()
    {
        return new NotifiedPeopleRetriever(
            $this->getUsersToNotifyDao(),
            $this->getUgroupsToNotifyDao(),
            $this->getItemFactory(),
            $this->getUGroupManager()
        );
    }

    private function getUGroupUpdater()
    {
        return new UgroupsUpdater($this->getUgroupsToNotifyDao());
    }

    private function getUserUpdater()
    {
        return new UsersUpdater($this->getUsersToNotifyDao());
    }

    /**
     * @return UsersToNotifyDao
     */
    private function getUsersToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    /**
     * @return UgroupsToNotifyDao
     */
    private function getUgroupsToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    public function getItemFactory()
    {
        return new Docman_ItemFactory();
    }
}
