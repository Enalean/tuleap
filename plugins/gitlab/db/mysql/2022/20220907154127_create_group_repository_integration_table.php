<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
final class b20220907154127_create_group_repository_integration_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Creates the plugin_gitlab_group_repository_integration table';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_gitlab_group_repository_integration',
            'CREATE TABLE IF NOT EXISTS plugin_gitlab_group_repository_integration (
                    group_id INT(11) NOT NULL,
                    integration_id INT(11) NOT NULL,
                    PRIMARY KEY(group_id, integration_id)
                    ) ENGINE = InnoDB'
        );
    }
}
