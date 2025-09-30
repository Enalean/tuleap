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
final class b202107271515_add_possibly_missing_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the (possibly) missing tables of the baseline plugin';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_baseline_baseline',
            'CREATE TABLE plugin_baseline_baseline
                (
                    id int auto_increment primary key,
                    name varchar(255) not null,
                    artifact_id int not null,
                    user_id int not null,
                    snapshot_date int not null
                )'
        );
        $this->api->createTable(
            'plugin_baseline_comparison',
            'CREATE TABLE plugin_baseline_comparison
                (
                    id int auto_increment primary key,
                    name varchar(255) null,
                    comment varchar(255) null,
                    base_baseline_id int not null,
                    compared_to_baseline_id int not null,
                    user_id int not null,
                    creation_date int not null
                )'
        );
        $this->api->createTable(
            'plugin_baseline_role_assignment',
            'CREATE TABLE plugin_baseline_role_assignment
                (
                    id int auto_increment primary key,
                    user_group_id int not null,
                    role varchar(255) not null,
                    project_id int not null
                )'
        );
    }
}
