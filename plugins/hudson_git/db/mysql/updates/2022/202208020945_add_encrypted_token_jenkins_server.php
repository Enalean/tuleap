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
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
final class b202208020945_add_encrypted_token_jenkins_server extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add a column in Jenkins server tables to store an encrypted token';
    }

    public function up(): void
    {
        if (! $this->api->columnNameExists('plugin_hudson_git_server', 'encrypted_token')) {
            $this->api->dbh->exec('ALTER TABLE plugin_hudson_git_server ADD COLUMN encrypted_token BLOB DEFAULT NULL');
        }

        if (! $this->api->columnNameExists('plugin_hudson_git_project_server', 'encrypted_token')) {
            $this->api->dbh->exec('ALTER TABLE plugin_hudson_git_project_server ADD COLUMN encrypted_token BLOB DEFAULT NULL');
        }
    }
}
