<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

use PFUser;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\SvnPermissionManager;

class RepositoryRepresentationBuilder
{
    /**
     * @var SvnPermissionManager
     */
    private $permission_manager;

    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_history_factory;
    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var NotificationsBuilder
     */
    private $notification_list_builder;

    public function __construct(
        SvnPermissionManager $permission_manager,
        HookConfigRetriever $hook_config_retriever,
        ImmutableTagFactory $immutable_tag_factory,
        AccessFileHistoryFactory $access_file_history_factory,
        MailNotificationManager $mail_notification_manager,
        NotificationsBuilder $notification_list_builder,
    ) {
        $this->permission_manager          = $permission_manager;
        $this->hook_config_retriever       = $hook_config_retriever;
        $this->immutable_tag_factory       = $immutable_tag_factory;
        $this->access_file_history_factory = $access_file_history_factory;
        $this->mail_notification_manager   = $mail_notification_manager;
        $this->notification_list_builder   = $notification_list_builder;
    }

    public function build(Repository $repository, PFUser $user)
    {
        if ($this->permission_manager->isAdmin($repository->getProject(), $user)) {
            $mail_notifications = $this->mail_notification_manager->getByRepository($repository);
            $notification_list  = $this->notification_list_builder->getNotifications($mail_notifications);
            return FullRepositoryRepresentation::fullBuild(
                $repository,
                $this->hook_config_retriever->getHookConfig($repository),
                $this->immutable_tag_factory->getByRepositoryId($repository),
                $this->access_file_history_factory->getCurrentVersion($repository),
                $notification_list
            );
        }

        return RepositoryRepresentation::build($repository);
    }
}
