<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202203171347_add_is_filename_pattern_enforced extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Add is_filename_pattern_enforced column in the 'plugin_docman_project_settings' table";
    }

    public function up(): void
    {
        $this->updateDefaultForFilenamePattern();
        $this->addIsEnforcedColumn();
    }

    private function updateDefaultForFilenamePattern(): void
    {
        $sql = "UPDATE plugin_docman_project_settings
                SET filename_pattern = ''
                WHERE filename_pattern IS NULL";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while updating null filename_pattern to empty string'
            );
        }
        $sql = "ALTER TABLE plugin_docman_project_settings
                ALTER COLUMN filename_pattern SET DEFAULT ''";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while updating filename_pattern default value in plugin_docman_project_settings'
            );
        }
    }

    private function addIsEnforcedColumn(): void
    {
        if ($this->api->columnNameExists('plugin_docman_project_settings', 'is_filename_pattern_enforced')) {
            return;
        }

        $sql = 'ALTER TABLE plugin_docman_project_settings
                ADD COLUMN is_filename_pattern_enforced TINYINT(1) DEFAULT 0
                AFTER filename_pattern';

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding is_filename_pattern_enforced column to the table plugin_docman_project_settings'
            );
        }
    }
}
