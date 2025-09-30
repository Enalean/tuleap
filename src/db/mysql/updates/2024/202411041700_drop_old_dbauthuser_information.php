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
final class b202411041700_drop_old_dbauthuser_information extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Drop old information related to the dbauthuser';
    }

    public function up(): void
    {
        $this->api->dbh->exec("DELETE FROM forgeconfig WHERE name IN ('sys_dbauth_user', 'sys_dbauth_passwd')");
    }
}
