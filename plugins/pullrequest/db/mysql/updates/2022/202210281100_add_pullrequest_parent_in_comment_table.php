<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
final class b202210281100_add_pullrequest_parent_in_comment_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add parent for pullrequets comments';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->addCommentForTableComments();
        $this->addCommentForTableInlineComments();
    }

    private function addCommentForTableComments(): void
    {
        if ($this->api->columnNameExists('plugin_pullrequest_comments', 'parent_id')) {
            return;
        }

        $sql = 'ALTER TABLE plugin_pullrequest_comments ADD COLUMN parent_id INT DEFAULT 0';

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding parent_id column to the table plugin_pullrequest_comments'
            );
        }
    }

    private function addCommentForTableInlineComments(): void
    {
        if ($this->api->columnNameExists('plugin_pullrequest_inline_comments', 'parent_id')) {
            return;
        }

        $sql = 'ALTER TABLE plugin_pullrequest_inline_comments ADD COLUMN parent_id INT DEFAULT 0';

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding parent_id column to the table plugin_pullrequest_inline_comments'
            );
        }
    }
}
