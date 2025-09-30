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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
final class b202102101100_add_private_comment_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create plugin_tracker_private_comment_disabled_tracker and plugin_tracker_private_comment_permission table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->createPluginTrackerPrivateCommentPermissionTable();
        $this->createPluginTrackerPrivateCommentDisabledTrackerTable();
    }

    private function createPluginTrackerPrivateCommentDisabledTrackerTable(): void
    {
        $sql = 'CREATE TABLE plugin_tracker_private_comment_disabled_tracker(
                    tracker_id INT(11) PRIMARY KEY
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_private_comment_disabled_tracker', $sql);
    }

    private function createPluginTrackerPrivateCommentPermissionTable(): void
    {
        $sql = 'CREATE TABLE plugin_tracker_private_comment_permission(
                     comment_id INT(11) NOT NULL,
                     ugroup_id int(11) NOT NULL,
                     PRIMARY KEY(comment_id, ugroup_id)
                ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_private_comment_permission', $sql);
    }
}
