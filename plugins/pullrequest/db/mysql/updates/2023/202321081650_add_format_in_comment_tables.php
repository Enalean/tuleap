<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
final class b202321081650_add_format_in_comment_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add format for pullrequets comment tables';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->addFormatForTableComments();
        $this->addFormatForTableInlineComments();
    }

    private function addFormatForTableComments(): void
    {
        if ($this->api->columnNameExists('plugin_pullrequest_comments', 'format')) {
            return;
        }

        $sql = "ALTER TABLE plugin_pullrequest_comments ADD COLUMN format VARCHAR(10) NOT NULL DEFAULT 'text'";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding format column to the table plugin_pullrequest_comments'
            );
        }
    }

    private function addFormatForTableInlineComments(): void
    {
        if ($this->api->columnNameExists('plugin_pullrequest_inline_comments', 'format')) {
            return;
        }

        $sql = "ALTER TABLE plugin_pullrequest_inline_comments ADD COLUMN format VARCHAR(10) NOT NULL DEFAULT 'text'";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding format column to the table plugin_pullrequest_inline_comments'
            );
        }
    }
}
