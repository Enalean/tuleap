<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202101131515_add_scaled_agile_service extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the scaled agile service in projects';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
                SELECT DISTINCT group_id , 'plugin_scaled_agile:service_lbl_key', 'plugin_scaled_agile:service_desc_key', 'plugin_scaled_agile', NULL, 1 , 0 , 'system',  153
                FROM service
                WHERE short_name != 'plugin_scaled_agile'";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'Could not add the scaled agile service in projects'
            );
        }
    }
}
