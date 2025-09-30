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

use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\VariableBoolean;
use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\VariableInteger;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202502121521_import_webdav_configuration_variables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Import webdav configuration variables';
    }

    public function up(): void
    {
        $importer = new \Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\ImportConfigVariablesToDb($this->api->dbh, '/etc/tuleap/plugins/webdav/etc/webdav.inc');
        $importer->import(
            [
                VariableInteger::withNewName('max_file_size', 'webdav_max_file_size', 2147583647),
                VariableBoolean::withNewName('write_access_enabled', 'webdav_write_access_enabled', false),
            ]
        );
    }
}
