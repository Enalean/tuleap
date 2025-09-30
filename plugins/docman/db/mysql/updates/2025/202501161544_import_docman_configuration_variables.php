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
use Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\VariableString;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202501161544_import_docman_configuration_variables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Import docman configuration variables';
    }

    public function up(): void
    {
        $importer = new \Tuleap\ForgeUpgrade\Bucket\ConfigVariableImportToDb\ImportConfigVariablesToDb($this->api->dbh, '/etc/tuleap/plugins/docman/etc/docman.inc');
        $importer->import(
            [
                VariableString::withSameName('docman_root', '/var/lib/tuleap/docman'),
                VariableBoolean::withNewName('embedded_are_allowed', 'docman_embedded_are_allowed', true),
                VariableBoolean::withNewName('only_siteadmin_can_delete', 'docman_only_siteadmin_can_delete', false),
            ]
        );
    }
}
