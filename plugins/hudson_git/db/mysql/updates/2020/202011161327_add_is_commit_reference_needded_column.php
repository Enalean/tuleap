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

class b202011161327_add_is_commit_reference_needded_column extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description(): string
    {
        return 'Add is_commit_reference_needed column';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'ALTER TABLE plugin_hudson_git_server
                ADD COLUMN is_commit_reference_needed BOOL NOT NULL DEFAULT TRUE';

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError(
                'An error occurred while adding is_commit_reference_needed to plugin_hudson_git_server table.'
            );
        }
    }
}
