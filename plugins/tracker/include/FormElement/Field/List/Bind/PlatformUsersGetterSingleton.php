<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\List\Bind;

use Tuleap\Tracker\FormElement\Field\List\Bind\User\ListFieldUserBindValue;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\User\ProvideUserFromRow;

final class PlatformUsersGetterSingleton implements PlatformUsersGetter
{
    private static ?self $instance;

    private ?array $registered_users = null;

    public function __construct(
        private readonly PlatformUsersGetterDao $dao,
        private readonly ProvideUserFromRow $user_from_row_provider,
        private readonly ProvideUserAvatarUrl $user_avatar_url_provider,
    ) {
    }

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(new PlatformUsersGetterDao(), \UserManager::instance(), new UserAvatarUrlProvider(new AvatarHashDao()));
        }
        return self::$instance;
    }

    /**
     * @return array<int, ListFieldUserBindValue>
     */
    #[\Override]
    public function getRegisteredUsers(\UserHelper $user_helper): array
    {
        if ($this->registered_users !== null) {
            return $this->registered_users;
        }

        $users_fullname = [];
        $users          = [];

        foreach ($this->dao->getRegisteredUsers($user_helper->getDisplayNameSQLQuery(), $user_helper->getDisplayNameSQLOrder()) as $row) {
            $users_fullname[$row['user_id']] = $row['full_name'];
            $users[]                         = $this->user_from_row_provider->getUserInstanceFromRow($row);
        }

        $this->registered_users = [];
        foreach ($this->user_avatar_url_provider->getAvatarUrls(...$users) as $user_with_avatar) {
            $user                             = $user_with_avatar->user;
            $user_id                          = (int) $user->getId();
            $this->registered_users[$user_id] = ListFieldUserBindValue::fromUser(
                $user_with_avatar,
                $users_fullname[$user_id] ?? $user->getUserName(),
            );
        }

        return $this->registered_users;
    }
}
