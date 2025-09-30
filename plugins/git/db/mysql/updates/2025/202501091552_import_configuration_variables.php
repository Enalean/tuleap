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

use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\VariableInteger;
use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\VariableString;
use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\ImportConfigVariablesToDb;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202501091552_import_configuration_variables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Import configuration variables';
    }

    public function up(): void
    {
        $importer = new ImportConfigVariablesToDb($this->api->dbh, '/etc/tuleap/plugins/git/etc/config.inc');
        $importer->import(
            [
                VariableString::withSameName('git_backup_dir', '/tmp'),
                VariableInteger::withNewName('weeks_number', 'git_weeks_number', 12),
                VariableString::withSameName('git_ssh_url', 'ssh://gitolite@%server_name%/'),
                VariableString::withSameName('git_http_url', 'https://%server_name%/plugins/git'),
            ]
        );
    }
}
