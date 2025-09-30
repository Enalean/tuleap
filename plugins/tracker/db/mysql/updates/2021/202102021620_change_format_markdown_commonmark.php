<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
final class b202102021620_change_format_markdown_commonmark extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Rename format from Markdown to CommonMark (comments and text fields)';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql_comment = 'UPDATE tracker_changeset_comment SET body_format = "commonmark" WHERE body_format = "markdown"';
        if ($this->db->dbh->exec($sql_comment) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while changing the body format of the changeset comments'
            );
        }

        $sql_text_fields = 'UPDATE tracker_changeset_value_text SET body_format = "commonmark" WHERE body_format = "markdown"';
        if ($this->db->dbh->exec($sql_text_fields) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while changing the body format of the text fields'
            );
        }
    }
}
