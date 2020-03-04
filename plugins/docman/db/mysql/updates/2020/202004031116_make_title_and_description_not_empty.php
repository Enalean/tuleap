<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

class b202004031116_make_title_and_description_not_empty extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description(): string
    {
        return 'Update docman_items table and make title and description not NULL';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "alter table plugin_docman_item modify title text not null";
        $this->db->alterTable(
            'plugin_docman_item',
            'tuleap',
            'title',
            $sql
        );

        $sql = "alter table plugin_docman_item modify description text not null";
        $this->db->alterTable(
            'plugin_docman_item',
            'tuleap',
            'description',
            $sql
        );
    }
}
