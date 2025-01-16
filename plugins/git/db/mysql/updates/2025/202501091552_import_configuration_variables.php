<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202501091552_import_configuration_variables extends \Tuleap\ForgeUpgrade\Bucket
{
    private const CONFIG_FILE = '/etc/tuleap/plugins/git/etc/config.inc';

    public function description()
    {
        return 'Import configuration variables';
    }

    public function up(): void
    {
        $variables = $this->getVariables();
        if (count($variables) === 0) {
            return;
        }

        $this->api->dbh->beginTransaction();
        $insert_stmt = $this->api->dbh->prepare('INSERT IGNORE INTO forgeconfig(name, value) VALUE (?, ?)');
        if (isset($variables['git_backup_dir'])) {
            $insert_stmt->execute(['git_backup_dir', $variables['git_backup_dir']]);
        }

        if (isset($variables['weeks_number'])) {
            $insert_stmt->execute(['git_weeks_number', $variables['weeks_number']]);
        }

        if (isset($variables['git_ssh_url'])) {
            $insert_stmt->execute(['git_ssh_url', $variables['git_ssh_url']]);
        }

        if (isset($variables['git_http_url'])) {
            $insert_stmt->execute(['git_http_url', $variables['git_http_url']]);
        }

        if (rename(self::CONFIG_FILE, self::CONFIG_FILE . '.tuleapsave_' . time())) {
            $this->api->dbh->commit();
        } else {
            $this->api->dbh->rollBack();
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Could not rename the git configuration file.');
        }
    }

    private function getVariables(): array
    {
        if (! file_exists(self::CONFIG_FILE)) {
            return [];
        }
        include self::CONFIG_FILE;
        return get_defined_vars();
    }
}
