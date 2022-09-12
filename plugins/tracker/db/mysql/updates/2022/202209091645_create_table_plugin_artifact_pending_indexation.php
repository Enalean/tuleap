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

final class b202209091645_create_table_plugin_artifact_pending_indexation extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create table plugin_tracker_artifact_pending_indexation';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_tracker_artifact_pending_indexation',
            'CREATE TABLE plugin_tracker_artifact_pending_indexation(
                    id int(11) NOT NULL PRIMARY KEY
                ) ENGINE=InnoDB;'
        );
    }
}
