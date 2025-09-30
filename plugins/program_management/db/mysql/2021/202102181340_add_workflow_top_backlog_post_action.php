<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202102181340_add_workflow_top_backlog_post_action extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add a workflow post action to add to the program top backlog';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE plugin_program_management_workflow_action_add_top_backlog (
                    id INT(11) PRIMARY KEY AUTO_INCREMENT,
                    transition_id INT(11) NOT NULL,
                    INDEX idx_transition_id (transition_id)
                ) ENGINE = InnoDB';

        $this->db->createTable('plugin_program_management_workflow_action_add_top_backlog', $sql);
    }
}
