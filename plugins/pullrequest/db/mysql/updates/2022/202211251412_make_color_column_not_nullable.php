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
final class b202211251412_make_color_column_not_nullable extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add color for pullrequets comments';
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
        $sql = "ALTER TABLE plugin_pullrequest_comments MODIFY COLUMN color VARCHAR(50) NOT NULL default ''";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding color column to the table plugin_pullrequest_comments'
            );
        }
    }

    private function addCommentForTableInlineComments(): void
    {
        $sql = "ALTER TABLE plugin_pullrequest_inline_comments MODIFY COLUMN color VARCHAR(50) NOT NULL default ''";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding color column to the table plugin_pullrequest_inline_comments'
            );
        }
    }
}
