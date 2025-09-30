<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
class b202105201803_move_done_semantic_to_tracker_plugin extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Move done semantic to Tracker plugin.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql_create_table = '
            CREATE TABLE plugin_tracker_semantic_done (
                 tracker_id INT(11) NOT NULL,
                 value_id INT(11) NOT NULL,
                 PRIMARY KEY(tracker_id, value_id),
                 INDEX semantic_done_tracker_idx(tracker_id)
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_tracker_semantic_done', $sql_create_table);

        if ($this->db->tableNameExists('plugin_agiledashboard_semantic_done')) {
            $sql_insert = '
                INSERT INTO plugin_tracker_semantic_done (tracker_id, value_id)
                SELECT tracker_id, value_id
                FROM plugin_agiledashboard_semantic_done;
            ';

            if ($this->db->dbh->exec($sql_insert) === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                    'An error occurred while moving the done semantic data'
                );
            }
        }
    }
}
