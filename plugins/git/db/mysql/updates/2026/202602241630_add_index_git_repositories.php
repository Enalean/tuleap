<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
final class b202602241630_add_index_git_repositories extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add indexes on the Git repositories table';
    }

    public function up(): void
    {
        $this->api->addIndex(
            'plugin_git',
            'idx_deletion_project_scope',
            'ALTER TABLE plugin_git ADD INDEX idx_deletion_project_scope(repository_deletion_date, project_id, repository_scope)'
        );
        $this->api->addIndex(
            'plugin_git',
            'idx_creation_user',
            'ALTER TABLE plugin_git ADD INDEX idx_creation_user(repository_creation_user_id)'
        );
    }
}
