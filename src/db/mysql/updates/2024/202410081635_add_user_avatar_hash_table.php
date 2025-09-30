<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202410081635_add_user_avatar_hash_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create new table user_avatar_hash';
    }

    public function up(): void
    {
        $this->api->createTable(
            'user_avatar_hash',
            <<<EOS
            CREATE TABLE user_avatar_hash (
                user_id int(11) NOT NULL PRIMARY KEY,
                hash VARCHAR(255)
            )
            EOS
        );
    }
}
