<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use HTTPRequest;
use MailBuilder;
use PermissionsOverrider_PermissionsOverriderManager;
use Project;
use TemplateRendererFactory;
use Tuleap\Docman\ExternalLinks\DocmanLinkProvider;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\ExternalLinks\LegacyLinkProvider;
use Tuleap\Docman\ResponseFeedbackWrapper;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;
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

    public function __construct(ResponseFeedbackWrapper $feedback, Project $project)
    {
        $this->feedback = $feedback;
        $this->project  = $project;
    }

    public function buildNotificationManager()
    {
        return new Docman_NotificationsManager(
            $this->project,
            $this->getProvider($this->project),
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
            $this->getProvider($this->project),
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
            $this->getProvider($this->project),
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
            $this->getProvider($this->project),
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
            $this->getProvider($this->project),
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
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    PermissionsOverrider_PermissionsOverriderManager::instance(),
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
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

    private function getProvider(Project $project): ILinkUrlProvider
    {
        $provider = new LegacyLinkProvider(
            HTTPRequest::instance()->getServerUrl() . '/plugins/docman/?group_id=' . urlencode((string) $project->getID())
        );
        $link_provider = new DocmanLinkProvider($project, $provider);
        EventManager::instance()->processEvent($link_provider);

        return $link_provider->getProvider();
    }
}
