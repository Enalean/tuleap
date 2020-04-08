<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202004071345_create_oidc_signing_key_table extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add plugin_oauth2_oidc_signing_key table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE plugin_oauth2_oidc_signing_key (
                    enforce_one_row_table ENUM(\'SHOULD_HAVE_AT_MOST_ONE_ROW\') NOT NULL PRIMARY KEY DEFAULT \'SHOULD_HAVE_AT_MOST_ONE_ROW\',
                    public_key TEXT NOT NULL,
                    private_key BLOB NOT NULL
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_oauth2_oidc_signing_key', $sql);
    }
}
