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

class b202010121049_improve_workflow_transition_index extends \Tuleap\ForgeUpgrade\Bucket //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function description(): string
    {
        return 'Replace tracker_workflow_transition index idx_wf_workflow_id by a more efficient one';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'ALTER TABLE tracker_workflow_transition
                DROP INDEX idx_wf_workflow_id,
                ADD INDEX idx_wf_workflow_id (workflow_id, transition_id)';

        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException($error_message);
        }
    }
}
