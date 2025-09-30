<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
class b202106161205_multiple_trackers_in_roadmap extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add table to handle multiple trackers in roadmap widget';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->execDB(
            'CREATE TABLE IF NOT EXISTS plugin_roadmap_widget_trackers (
                plugin_roadmap_widget_id INT(11) UNSIGNED NOT NULL,
                tracker_id INT(11) NOT NULL,
                PRIMARY KEY (plugin_roadmap_widget_id, tracker_id)
            ) ENGINE=InnoDB;',
            'Unable to create table plugin_roadmap_widget_trackers'
        );

        $this->execDB(
            'INSERT INTO plugin_roadmap_widget_trackers (plugin_roadmap_widget_id, tracker_id)
            SELECT id, tracker_id
            FROM plugin_roadmap_widget',
            'Unable to populate new table plugin_roadmap_widget_trackers'
        );
    }

    private function execDB(string $sql, string $message): void
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
