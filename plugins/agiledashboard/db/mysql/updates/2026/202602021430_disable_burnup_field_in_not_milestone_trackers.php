<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
final class b202602021430_disable_burnup_field_in_not_milestone_trackers extends \Tuleap\ForgeUpgrade\Bucket
{
    #[Override]
    public function description(): string
    {
        return 'Set column use_it of tracker_field to 0 for each burnup in a tracker which is not a milestone';
    }

    #[Override]
    public function up(): void
    {
        $this->api->dbh->exec(
            <<<SQL
            UPDATE tracker_field field
            LEFT JOIN plugin_agiledashboard_planning planning ON planning.planning_tracker_id = field.tracker_id
            SET field.use_it = 0
            WHERE planning.id IS NULL AND field.formElement_type = 'burnup'
            SQL
        );
    }
}
