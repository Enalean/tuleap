<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202101290900_add_author_data_merge_request_columns extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add author_name and author_email in plugin_gitlab_merge_request_info table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->alterTable(
            'plugin_gitlab_merge_request_info',
            'tuleap',
            'author_name',
            'ALTER TABLE plugin_gitlab_merge_request_info
                ADD COLUMN author_name TEXT DEFAULT NULL'
        );

        $this->db->alterTable(
            'plugin_gitlab_merge_request_info',
            'tuleap',
            'author_email',
            'ALTER TABLE plugin_gitlab_merge_request_info
                ADD COLUMN author_email TEXT DEFAULT NULL'
        );
    }
}
