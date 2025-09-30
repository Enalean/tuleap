<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202207281459_add_colum_has_error_on_team_synchronisation extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the plugin_program_management_team_synchronizations_pending table to register pending teams synchronizations.';
    }

    public function up(): void
    {
        if ($this->api->columnNameExists('plugin_program_management_team_synchronizations_pending', 'has_error')) {
            return;
        }

        $result = $this->api->dbh->exec('ALTER TABLE plugin_program_management_team_synchronizations_pending ADD COLUMN has_error BOOL NOT NULL DEFAULT FALSE');

        if ($result === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding the has_error column'
            );
        }
    }
}
