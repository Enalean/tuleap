<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202009291619_create_invitations_table extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add invitations table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "CREATE TABLE invitations(
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            created_on INT(11) NOT NULL,
            from_user_id INT(11) NOT NULL,
            to_email TEXT NOT NULL,
            to_user_id INT(11) NULL,
            custom_message TEXT NULL,
            status VARCHAR(10),
            INDEX idx(created_on, from_user_id)
        ) ENGINE=InnoDB";

        $this->db->createTable('invitations', $sql);
    }
}
