<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
final class b202108271130_add_pending_program_increment_update_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the plugin_program_management_pending_program_increment_update table';
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_pending_program_increment_update (
            id                   INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            program_increment_id INT(11) NOT NULL,
            user_id              INT(11) NOT NULL,
            changeset_id         INT(11) NOT NULL,
            INDEX idx_program_increment_id(program_increment_id)
        ) ENGINE=InnoDB';

        $this->api->createTable('plugin_program_management_pending_program_increment_update', $sql);
    }
}
