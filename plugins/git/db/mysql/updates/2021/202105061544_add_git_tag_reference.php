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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class b202105061544_add_git_tag_reference extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add git_tag reference';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->dbh->beginTransaction();
        $this->createReference();
        $this->addReferenceInProjects();
        $this->db->dbh->commit();
    }

    private function createReference(): void
    {
        $sql = "INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (33, 'git_tag', 'plugin_git:reference_tag_desc_key', '/plugins/git/index.php/\$group_id/view/\$1/?a=tag&h=\$2', 'S', 'plugin_git', 'git_tag');";

        $this->executeSql($sql);
    }

    private function addReferenceInProjects(): void
    {
        $sql = 'INSERT INTO reference_group (reference_id, group_id, is_active)
                SELECT 33, group_id, 1 FROM `groups` WHERE group_id';

        $this->executeSql($sql);
    }

    public function executeSql($sql): void
    {
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            $this->rollBackOnError($error_message);
        }
    }
}
