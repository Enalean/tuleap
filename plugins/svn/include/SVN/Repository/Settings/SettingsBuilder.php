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
        $commit_rules      = [];
        $immutable_tag     = $this->immutable_tag_factory->getEmpty($repository);
        $access_file       = "";
        $mail_notification = [];

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

        if ($settings->access_file) {
            $access_file = $settings->access_file;
        }

        if ($settings->email_notifications) {
            foreach ($settings->email_notifications as $notification) {
                $users_notification = [];
                if ($notification->users) {
                    foreach ($notification->users as $user_id) {
                        $user = $this->user_manager->getUserById($user_id);
                        if (! $user) {
                            return Result::err(Fault::fromMessage("User $user_id not found"));
                        }
                        $users_notification[] = $user;
                    }
                }

                $user_groups_notification = [];
                if ($notification->user_groups) {
                    foreach ($notification->user_groups as $group_id) {
                        $user_group = $this->user_group_retriever->getExistingUserGroup($group_id);

                        if (! $user_group->isStatic() && ! $this->isAnAuthorizedDynamicUgroup($user_group)) {
                            return Result::err(Fault::fromMessage(
                                "Notifications can not be sent to ugroups Anonymous Authenticated and Registered"
                            ));
                        }

                        if ($user_group->getProject()->getId() != $repository->getProject()->getID()) {
                            return Result::err(Fault::fromMessage(
                                "You can't add a user group from a different project"
                            ));
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
            )
        );
    }

    private function isAnAuthorizedDynamicUgroup(ProjectUGroup $project_user_group): bool
    {
        return $project_user_group->getId() == ProjectUGroup::PROJECT_MEMBERS ||
            $project_user_group->getId() == ProjectUGroup::PROJECT_ADMIN;
    }
}
