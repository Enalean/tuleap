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
final class b202102100900_rename_plugin extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Rename the plugin to "Program Management"';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->updatePluginName();
        $this->updatePluginService();
        $this->renameToNewTables();
    }

    private function updatePluginName(): void
    {
        $res = $this->db->dbh->exec('UPDATE plugin SET name = "program_management" WHERE name = "scaled_agile"');
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while updating the name of the program management plugin'
            );
        }
    }

    private function updatePluginService(): void
    {
        $res = $this->db->dbh->exec('UPDATE service SET short_name = "plugin_program_management", label = "plugin_program_management:service_lbl_key", description = "plugin_program_management:service_desc_key" WHERE short_name = "plugin_scaled_agile"');
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while updating the program management services'
            );
        }
    }

    private function renameToNewTables(): void
    {
        $this->renameTables('plugin_scaled_agile_team_projects', 'plugin_program_management_team_projects');
        $this->renameTables('plugin_scaled_agile_pending_mirrors', 'plugin_program_management_pending_mirrors');
        $this->renameTables('plugin_scaled_agile_plan', 'plugin_program_management_plan');
        $this->renameTables('plugin_scaled_agile_can_prioritize_features', 'plugin_program_management_can_prioritize_features');
        $this->renameTables('plugin_scaled_agile_explicit_top_backlog', 'plugin_program_management_explicit_top_backlog');
    }

    private function renameTables(string $old_name, string $new_name): void
    {
        if ($this->db->tableNameExists($new_name)) {
            return;
        }
        $res = $this->db->dbh->exec(sprintf('ALTER TABLE `%s` RENAME `%s`', $old_name, $new_name));
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while renaming ' . $new_name
            );
        }
    }
}
