<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Docman_PermissionsManager;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Project;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Docman\REST\v1\DocmanItemsRequestBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\REST\UserRepresentation;
use UGroupManager;
use UserManager;

final class NotificationsSubscribersController extends DispatchablePSR15Compatible
{
    public function __construct(
        private UserManager $user_manager,
        private UGroupManager $ugroup_manager,
        private UsersToNotifyDao $user_dao,
        private UgroupsToNotifyDao $ugroup_dao,
        private DocmanItemsRequestBuilder $item_request_builder,
        private JSONResponseBuilder $json_response_builder,
        private UserAvatarUrlProvider $user_avatar_url_provide,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $item_id       = (int) $request->getAttribute('item_id');
        $items_request = $this->item_request_builder->buildFromItemId($item_id);
        $project       = $items_request->getProject();
        $current_user  = $this->user_manager->getCurrentUser();

        if (! Docman_PermissionsManager::instance((int) $project->getID())->userCanManage($current_user, $item_id)) {
            $current_notification = [];
            $users_iterator       = $this->user_dao->search($current_user->getId(), $item_id, PLUGIN_DOCMAN_NOTIFICATION);
            if ($users_iterator !== false && sizeof($users_iterator) > 0) {
                foreach ($users_iterator as $user) {
                    $user_representation    = UserRepresentation::build($current_user, $this->user_avatar_url_provide);
                    $current_notification[] = ['subscriber' => $user_representation, 'subscription_type' => $user['type']];
                }
            }
            $users_iterator = $this->user_dao->search($current_user->getId(), $item_id, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
            if ($users_iterator !== false && sizeof($users_iterator) > 0) {
                foreach ($users_iterator as $user) {
                    $user_representation    = UserRepresentation::build($current_user, $this->user_avatar_url_provide);
                    $current_notification[] = ['subscriber' => $user_representation, 'subscription_type' => $user['type']];
                }
            }
            return $this->json_response_builder->fromData([
                'users' => $current_notification,
                'ugroups' => [],
            ])->withStatus(200);
        }

        $users_to_notify_for_item    = $this->getUsersToNotifyForItem($item_id);
        $user_ids_to_notify_for_item = array_map(fn($subscription): int => $subscription['subscriber']->id, $users_to_notify_for_item);

        $ugroups_to_notify_for_item    = $this->getUgroupsToNotifyForItem($item_id, $project);
        $ugroup_ids_to_notify_for_item = array_map(fn($subscription): string => $subscription['subscriber']->id, $ugroups_to_notify_for_item);

        $parent_id = $items_request->getItem()->getParentId();
        if ($parent_id) {
            $users_to_notify_from_parent = $this->getUsersToNotifyFromParent($parent_id, $user_ids_to_notify_for_item);
            $users_to_notify_for_item    = array_merge($users_to_notify_for_item, $users_to_notify_from_parent);

            $ugroups_to_notify_from_parent = $this->getUGroupsToNotifyFromParent($parent_id, $project, $ugroup_ids_to_notify_for_item);
            $ugroups_to_notify_for_item    = array_merge($ugroups_to_notify_for_item, $ugroups_to_notify_from_parent);
        }


        return $this->json_response_builder->fromData([
            'users' => $users_to_notify_for_item,
            'ugroups' => $ugroups_to_notify_for_item,
        ])->withStatus(200);
    }

    private function getUsersToNotifyForItem(int $item_id): array
    {
        $users_to_notify = [];
        $users_iterator  = $this->user_dao->searchUserIdByObjectIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION);
        if ($users_iterator !== false && sizeof($users_iterator) > 0) {
            foreach ($users_iterator as $user) {
                $user_id = intval($user['user_id']);
                $pfuser  = $this->user_manager->getUserById($user_id);
                if ($pfuser === null) {
                    continue;
                }
                $user_representation = UserRepresentation::build($pfuser, $this->user_avatar_url_provide);
                $user_subscription   = $this->isUserSubscribedToTheEntireSubhierarchy($item_id, (int) $user_representation->id)
                    ? ['subscriber' => $user_representation, 'subscription_type' => PLUGIN_DOCMAN_NOTIFICATION_CASCADE]
                    : ['subscriber' => $user_representation, 'subscription_type' => PLUGIN_DOCMAN_NOTIFICATION];
                $users_to_notify[]   = $user_subscription;
            }
        }
        return $users_to_notify;
    }

    private function getUsersToNotifyFromParent(int $item_id, array $ids_to_ignore): array
    {
        $users_to_notify = [];
        $items_request   = $this->item_request_builder->buildFromItemId($item_id);
        $users_iterator  = $this->user_dao->searchUserIdByObjectIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        if ($users_iterator !== false && sizeof($users_iterator) > 0) {
            foreach ($users_iterator as $user) {
                $user_id = intval($user['user_id']);
                if (in_array($user_id, $ids_to_ignore)) {
                    continue;
                }
                $pfuser = $this->user_manager->getUserById($user_id);
                if ($pfuser === null) {
                    continue;
                }
                $user_representation = UserRepresentation::build($pfuser, $this->user_avatar_url_provide);
                $ids_to_ignore[]     = $user_id;
                $users_to_notify[]   = ['subscriber' => $user_representation, 'subscription_type' => 'from_parent'];
            }
        }
        $parent_id = $items_request->getItem()->getParentId();
        if ($parent_id) {
            $users_to_notify = array_merge($users_to_notify, $this->getUsersToNotifyFromParent($parent_id, $ids_to_ignore));
        }

        return $users_to_notify;
    }

    private function getUgroupsToNotifyForItem(int $item_id, Project $project): array
    {
        $ugroups_to_notify = [];
        $ugroups_iterator  = $this->ugroup_dao->searchUgroupsByItemIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION);
        if ($ugroups_iterator !== false && sizeof($ugroups_iterator) > 0) {
            foreach ($ugroups_iterator as $ugroup) {
                $ugroup_id                 = $ugroup['ugroup_id'];
                $ugroup                    = $this->ugroup_manager->getById($ugroup_id);
                $user_group_representation =  UserGroupRepresentation::build(
                    $project,
                    $ugroup,
                    $this->user_manager->getCurrentUser(),
                    \EventManager::instance(),
                );
                $ugroup_subscription       = $this->isUserGroupSubscribedToTheEntireSubhierarchy($item_id, $ugroup_id)
                    ? ['subscriber' => $user_group_representation, 'subscription_type' => PLUGIN_DOCMAN_NOTIFICATION_CASCADE]
                    : ['subscriber' => $user_group_representation, 'subscription_type' => PLUGIN_DOCMAN_NOTIFICATION];
                $ugroups_to_notify[]       = $ugroup_subscription;
            }
        }
        return $ugroups_to_notify;
    }

    private function getUGroupsToNotifyFromParent(int $item_id, Project $project, array $ids_to_ignore): array
    {
        $ugroups_to_notify = [];
        $items_request     = $this->item_request_builder->buildFromItemId($item_id);
        $ugroups_iterator  = $this->ugroup_dao->searchUgroupsByItemIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION);
        if ($ugroups_iterator !== false && sizeof($ugroups_iterator) > 0) {
            foreach ($ugroups_iterator as $ugroup) {
                $ugroup_id = $ugroup['ugroup_id'];
                if (in_array($ugroup_id, $ids_to_ignore)) {
                    continue;
                }
                $ugroup                    = $this->ugroup_manager->getById($ugroup_id);
                $user_group_representation =  UserGroupRepresentation::build(
                    $project,
                    $ugroup,
                    $this->user_manager->getCurrentUser(),
                    \EventManager::instance(),
                );

                $ids_to_ignore[]     = $ugroup_id;
                $ugroups_to_notify[] = ['subscriber' => $user_group_representation, 'subscription_type' => 'from_parent'];
            }
        }
        $parent_id = $items_request->getItem()->getParentId();
        if ($parent_id) {
            $ugroups_to_notify = array_merge($ugroups_to_notify, $this->getUGroupsToNotifyFromParent($parent_id, $project, $ids_to_ignore));
        }

        return $ugroups_to_notify;
    }

    private function isUserSubscribedToTheEntireSubhierarchy(int $item_id, int $user_id): bool
    {
        $users_iterator = $this->user_dao->searchUserIdByObjectIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        if ($users_iterator !== false && sizeof($users_iterator) > 0) {
            foreach ($users_iterator as $user) {
                if ($user_id === intval($user['user_id'])) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isUserGroupSubscribedToTheEntireSubhierarchy(int $item_id, string $ugroup_id): bool
    {
        $ugroups_iterator = $this->ugroup_dao->searchUgroupsByItemIdAndType($item_id, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        if ($ugroups_iterator !== false && sizeof($ugroups_iterator) > 0) {
            foreach ($ugroups_iterator as $ugroup) {
                if ($ugroup_id === $ugroup['ugroup_id']) {
                    return true;
                }
            }
        }
        return false;
    }
}
