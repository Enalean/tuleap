<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202401261122_rename_user_cce_to_functions extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Rename user forge__cce to forge_function';
    }

    public function up(): void
    {
        $sql = <<<SQL
            UPDATE user
            SET user_name = 'forge__function', realname = 'Tuleap Functions'
            WHERE user_id = 70
            SQL;
        $this->api->dbh->exec($sql);
    }
}
