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
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202207271424_add_team_synchronizations_pending_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the plugin_program_management_team_synchronizations_pending table to register pending teams synchronizations.';
    }

    public function up(): void
    {
        $sql = '
            CREATE TABLE plugin_program_management_team_synchronizations_pending (
                program_id INT(11) NOT NULL,
                team_id INT(11) NOT NULL,
                timestamp INT(11) NOT NULL
            ) ENGINE = InnoDB
        ';

        $this->api->createTable('plugin_program_management_team_synchronizations_pending', $sql);
    }
}
