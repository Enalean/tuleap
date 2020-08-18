<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202008171622_create_release_note_link_table extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add release_note_link table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "CREATE TABLE release_note_link (
                    enforce_one_row_table ENUM('SHOULD_HAVE_AT_MOST_ONE_ROW') NOT NULL PRIMARY KEY DEFAULT 'SHOULD_HAVE_AT_MOST_ONE_ROW',
                    actual_link TEXT,
                    tuleap_version TEXT NOT NULL
        )";

        $this->db->createTable('release_note_link', $sql);
    }
}
