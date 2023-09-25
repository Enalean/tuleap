<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202309250847_rename_in_new_dropdown_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Rename in_new_dropdown table";
    }

    public function up(): void
    {
        // Actually it is more a copy than a rename, so that we can bisect in the
        // past without having to worry about the loss of tables
        $this->api->createTable(
            "plugin_tracker_promoted",
            <<<EOS
            CREATE TABLE plugin_tracker_promoted(
                tracker_id int(11) NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB
            EOS
        );

        $this->api->dbh->exec(
            <<<EOS
            INSERT INTO plugin_tracker_promoted(tracker_id)
            SELECT old.tracker_id FROM plugin_tracker_in_new_dropdown AS old
            ON DUPLICATE KEY UPDATE tracker_id=old.tracker_id
            EOS
        );
    }
}
