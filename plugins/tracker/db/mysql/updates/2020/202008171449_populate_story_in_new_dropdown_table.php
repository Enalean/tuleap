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

class b202008171449_populate_story_in_new_dropdown_table extends \Tuleap\ForgeUpgrade\Bucket //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public function description(): string
    {
        return 'Add story trackers in plugin_tracker_in_new_dropdown table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'INSERT IGNORE INTO plugin_tracker_in_new_dropdown (tracker_id)
                SELECT id
                FROM tracker
                WHERE item_name IN ("story")
                  AND deletion_date IS NULL';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($error_message);
        }
    }
}
