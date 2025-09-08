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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_FormElement_Field_List_Bind_UsersValue;

final class PlatformUsersGetterSingleton implements PlatformUsersGetter
{
    private static ?self $instance;

    private ?array $registered_users;

    public function __construct(private readonly PlatformUsersGetterDao $dao, private readonly \UserManager $user_manager, private readonly \Tuleap\DB\DatabaseUUIDV7Factory $uuid_factory)
    {
    }

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(new PlatformUsersGetterDao(), \UserManager::instance(), new \Tuleap\DB\DatabaseUUIDV7Factory());
        }
        return self::$instance;
    }

    /**
     * @return array<int, Tracker_FormElement_Field_List_Bind_UsersValue>
     */
    #[\Override]
    public function getRegisteredUsers(\UserHelper $user_helper): array
    {
        if (! isset($this->registered_users)) {
            $this->registered_users = [];
            foreach ($this->dao->getRegisteredUsers($user_helper->getDisplayNameSQLQuery(), $user_helper->getDisplayNameSQLOrder()) as $row) {
                $this->registered_users[$row['user_id']] = Tracker_FormElement_Field_List_Bind_UsersValue::fromUser(
                    $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()),
                    $this->user_manager->getUserInstanceFromRow($row),
                    $row['full_name']
                );
            }
        }
        return $this->registered_users;
    }
}
