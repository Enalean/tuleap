<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
final class b202208231630_add_group_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Creates the table plugin_gitlab_group and plugin_gitlab_group_token';
    }

    public function up(): void
    {
        $this->createGitlabGroupTable();
        $this->createGitlabGroupTokenTable();
    }

    private function createGitlabGroupTable(): void
    {
        $sql = '
                CREATE TABLE IF NOT EXISTS plugin_gitlab_group (
                    id                     INT(11)      NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    gitlab_group_id        INT(11)      NOT NULL,
                    name                   VARCHAR(255) NOT NULL,
                    full_path              TEXT         NOT NULL,
                    web_url                VARCHAR(255) NOT NULL,
                    avatar_url             TEXT         DEFAULT NULL,
                    last_synchronization_date INT(11) NOT NULL,
                    allow_artifact_closure TINYINT(1)   NOT NULL DEFAULT 0,
                    prefix_branch_name     VARCHAR(255)          DEFAULT NULL,
                    UNIQUE(gitlab_group_id)
                ) ENGINE = InnoDB;
        ';
        $this->api->createTable('plugin_gitlab_group', $sql);

        if (! $this->api->tableNameExists('plugin_gitlab_group')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'Table plugin_gitlab_group has not been created in database'
            );
        }
    }

    private function createGitlabGroupTokenTable(): void
    {
        $sql = '
                CREATE TABLE IF NOT EXISTS plugin_gitlab_group_token (
                group_id INT(11) NOT NULL PRIMARY KEY,
                token BLOB NOT NULL
            ) ENGINE = InnoDB;
        ';

        $this->api->createTable('plugin_gitlab_group_token', $sql);

        if (! $this->api->tableNameExists('plugin_gitlab_group_token')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'Table plugin_gitlab_group_token has not been created in database'
            );
        }
    }
}
