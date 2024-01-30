<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\SVN\Repository\Settings;

use ProjectUGroup;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\REST\v1\NotificationPOSTPUTRepresentation;
use Tuleap\SVN\REST\v1\SettingsPOSTRepresentation;
use Tuleap\SVN\REST\v1\SettingsPUTRepresentation;
use Tuleap\SVNCore\Repository;
use Tuleap\User\RetrieveUserById;

class SettingsBuilder
{
    public function __construct(
        private readonly ImmutableTagFactory $immutable_tag_factory,
        private readonly RetrieveUserById $user_manager,
        private readonly UserGroupRetriever $user_group_retriever,
    ) {
    }

    /**
     * @return Ok<Settings> | Err<Fault>
     */
    public function buildFromPOSTPUTRESTRepresentation(
        Repository $repository,
        SettingsPOSTRepresentation | SettingsPUTRepresentation | null $settings,
    ): Ok|Err {
        $commit_rules            = [];
        $immutable_tag           = $this->immutable_tag_factory->getEmpty($repository);
        $access_file             = "";
        $mail_notification       = [];
        $has_default_permissions = true;

        if ($settings === null) {
            return Result::ok(
                new Settings(
                    $commit_rules,
                    $immutable_tag,
                    $access_file,
                    $mail_notification,
                    [],
                    1,
                    false,
                    true,
                )
            );
        }

        if ($settings->commit_rules) {
            $commit_rules = $settings->commit_rules->toArray();
        }

        if ($settings->immutable_tags) {
            $immutable_tag = $this->immutable_tag_factory->getFromRESTRepresentation(
                $repository,
                $settings->immutable_tags
            );
        }

        if ($settings instanceof SettingsPUTRepresentation) {
            if (! isset($settings->access_file)) {
                return Result::err(MissingAccessFileFault::build());
            }
        }

        if (isset($settings->has_default_permissions)) {
            $has_default_permissions = $settings->has_default_permissions;
        }

        if ($settings->access_file) {
            $access_file = $settings->access_file;
        }

        if ($settings->email_notifications) {
            $non_unique_path = $this->getNonUniquePaths($settings->email_notifications);
            if (! empty($non_unique_path)) {
                return Result::err(NonUniqueNotificationPathFault::build($non_unique_path));
            }

            $empty_notifications = $this->getEmptyNotifications($settings->email_notifications);
            if (! empty($empty_notifications)) {
                return Result::err(EmptyNotificationsFault::build($empty_notifications));
            }

            $non_unique_mails = $this->getNonUniqueEmails($settings->email_notifications);
            if (! empty($non_unique_mails)) {
                return Result::err(NonUniqueMailsFault::build($non_unique_mails));
            }

            foreach ($settings->email_notifications as $notification) {
                $users_notification = [];
                if ($notification->users) {
                    foreach ($notification->users as $user_id) {
                        $user = $this->user_manager->getUserById($user_id);
                        if (! $user) {
                            return Result::err(UserInNotificationNotFoundFault::build($user_id));
                        }
                        if (! $user->isAlive()) {
                            return Result::err(UserNotAliveFault::build($user_id));
                        }
                        $users_notification[] = $user;
                    }
                }

                $user_groups_notification = [];
                if ($notification->user_groups) {
                    foreach ($notification->user_groups as $group_id) {
                        $user_group = $this->user_group_retriever->getExistingUserGroup($group_id);

                        if (! $user_group->isStatic() && ! $this->isAnAuthorizedDynamicUgroup($user_group)) {
                            return Result::err(NonAuthorizedDynamicUgroupFault::build());
                        }

                        if ($user_group->getProject()->getId() != $repository->getProject()->getID()) {
                            return Result::err(UgroupNotInProjectFault::build());
                        }

                        $user_groups_notification[] = $user_group;
                    }
                }

                $mail_notification[] = new MailNotification(
                    0,
                    $repository,
                    $notification->path,
                    $notification->emails,
                    $users_notification,
                    $user_groups_notification
                );
            }
        }

        return Result::ok(
            new Settings(
                $commit_rules,
                $immutable_tag,
                $access_file,
                $mail_notification,
                [],
                1,
                false,
                $has_default_permissions,
            )
        );
    }

    /**
     * @param NotificationPOSTPUTRepresentation[] $email_notifications
     * @return list<string>
     */
    private function getNonUniquePaths(array $email_notifications): array
    {
        $non_unique_paths  = [];
        $already_seen_path = [];

        foreach ($email_notifications as $notification) {
            if (isset($already_seen_path[$notification->path])) {
                $non_unique_paths[] = $notification->path;
            }

            $already_seen_path[$notification->path] = true;
        }

        return $non_unique_paths;
    }

    /**
     * @param NotificationPOSTPUTRepresentation[] $email_notifications
     * @return list<string>
     */
    private function getEmptyNotifications(array $email_notifications): array
    {
        $empty_notifications = [];
        foreach ($email_notifications as $notification) {
            if (empty($notification->emails) && empty($notification->users) && empty($notification->user_groups)) {
                $empty_notifications[] = $notification->path;
            }
        }

        return $empty_notifications;
    }

    /**
     * @param NotificationPOSTPUTRepresentation[] $email_notifications
     */
    private function getNonUniqueEmails(array $email_notifications): array
    {
        $non_unique_mails = [];
        foreach ($email_notifications as $notification) {
            $duplicated_values = array_diff_key($notification->emails, array_unique($notification->emails));
            if (! empty($duplicated_values)) {
                $non_unique_mails[$notification->path] = $duplicated_values;
            }
        }

        return $non_unique_mails;
    }

    private function isAnAuthorizedDynamicUgroup(ProjectUGroup $project_user_group): bool
    {
        return $project_user_group->getId() == ProjectUGroup::PROJECT_MEMBERS ||
            $project_user_group->getId() == ProjectUGroup::PROJECT_ADMIN;
    }
}
