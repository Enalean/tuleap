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
 */

declare(strict_types=1);

class b202011141431_create_legacy_tracker_migration_table extends \Tuleap\ForgeUpgrade\Bucket //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public function description()
    {
        return 'Add plugin_tracker_legacy_tracker_migrated table to flag legacy tracker migrated keeping original artifact ids.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_legacy_tracker_migrated(
                    legacy_tracker_id int(11) NOT NULL PRIMARY KEY
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_legacy_tracker_migrated', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_legacy_tracker_migrated')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException(
                'plugin_tracker_legacy_tracker_migrated table is missing'
            );
        }
    }
}
