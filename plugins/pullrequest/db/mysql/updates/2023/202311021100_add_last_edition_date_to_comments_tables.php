<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
final class b202311021100_add_last_edition_date_to_comments_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add a last_edition_date column in pull-request comments and inline_comments tables';
    }

    public function up(): void
    {
        $this->addColumnToCommentsTable();
        $this->addColumnToInlineCommentsTable();
    }

    private function addColumnToCommentsTable(): void
    {
        if ($this->api->columnNameExists("plugin_pullrequest_comments", "last_edition_date")) {
            return;
        }

        $ok = $this->api->dbh->exec("ALTER TABLE plugin_pullrequest_comments ADD COLUMN last_edition_date INT(11) DEFAULT NULL");
        if ($ok === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding last_edition_date column to the table plugin_pullrequest_comments'
            );
        }
    }

    private function addColumnToInlineCommentsTable(): void
    {
        if ($this->api->columnNameExists("plugin_pullrequest_inline_comments", "last_edition_date")) {
            return;
        }

        $ok = $this->api->dbh->exec("ALTER TABLE plugin_pullrequest_inline_comments ADD COLUMN last_edition_date INT(11) DEFAULT NULL");
        if ($ok === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding last_edition_date column to the table plugin_pullrequest_inline_comments'
            );
        }
    }
}
