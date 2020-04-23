<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

class SOAP_TestDataBuilder extends TestDataBuilder
{
    public const PROJECT_PRIVATE_MEMBER_ID = 101;

    public function generateUsers()
    {
        $admin_user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);
        $admin_user->setPassword(new \Tuleap\Cryptography\ConcealedString(self::ADMIN_PASSWORD));
        $this->user_manager->updateDb($admin_user);

        $user_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_1->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_USER_1_PASS));
        $this->user_manager->updateDb($user_1);

        $user_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_USER_2_PASS));
        $this->user_manager->updateDb($user_2);

        return $this;
    }
}
