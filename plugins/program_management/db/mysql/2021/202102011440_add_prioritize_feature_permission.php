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
final class b202102011440_add_prioritize_feature_permission extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Introduce a permission level to prioritize feature';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE plugin_scaled_agile_can_prioritize_features(
                    program_increment_tracker_id INT(11) NOT NULL,
                    user_group_id INT(11) NOT NULL,
                    PRIMARY KEY (program_increment_tracker_id, user_group_id)
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_scaled_agile_can_prioritize_features', $sql);

        $sql_prefill_existing_plan = 'INSERT IGNORE INTO plugin_scaled_agile_can_prioritize_features(program_increment_tracker_id, user_group_id)
            SELECT DISTINCT program_increment_tracker_id, 4
            FROM plugin_scaled_agile_plan';
        $res                       = $this->db->dbh->exec($sql_prefill_existing_plan);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'Could not allow project administrators to prioritize features inside existing plans'
            );
        }
    }
}
