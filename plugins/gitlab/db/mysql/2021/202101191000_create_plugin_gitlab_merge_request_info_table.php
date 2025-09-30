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
final class b202101191000_create_plugin_gitlab_merge_request_info_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Creates the table plugin_gitlab_merge_request_info';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS plugin_gitlab_merge_request_info (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                repository_id INT(11) NOT NULL,
                merge_request_id INT(11) NOT NULL,
                title TEXT NOT NULL,
                UNIQUE KEY merge_request_id(repository_id, merge_request_id)
            ) ENGINE=InnoDB;
        ';

        $this->db->createTable('plugin_gitlab_merge_request_info', $sql);

        if (! $this->db->tableNameExists('plugin_gitlab_merge_request_info')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Table plugin_gitlab_merge_request_info has not been created in database');
        }
    }
}
