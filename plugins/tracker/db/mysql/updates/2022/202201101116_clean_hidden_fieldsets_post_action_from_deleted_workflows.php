<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
final class b202201101116_clean_hidden_fieldsets_post_action_from_deleted_workflows extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Clean Hidden fieldsets post actions from deleted Tracker workflows';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = '
            DELETE plugin_tracker_workflow_postactions_hidden_fieldsets_value, plugin_tracker_workflow_postactions_hidden_fieldsets
            FROM plugin_tracker_workflow_postactions_hidden_fieldsets_value
                JOIN plugin_tracker_workflow_postactions_hidden_fieldsets ON (
                    plugin_tracker_workflow_postactions_hidden_fieldsets_value.postaction_id = plugin_tracker_workflow_postactions_hidden_fieldsets.id
                )
            LEFT JOIN tracker_workflow_transition ON (
                    plugin_tracker_workflow_postactions_hidden_fieldsets.transition_id = tracker_workflow_transition.transition_id
                )
            WHERE tracker_workflow_transition.transition_id IS NULL;
        ';

        if ($this->db->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while deleting Hidden fieldsets post actions from deleted Tracker workflows.'
            );
        }
    }
}
