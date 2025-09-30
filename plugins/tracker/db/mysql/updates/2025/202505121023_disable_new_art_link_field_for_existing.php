<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Tuleap\ForgeUpgrade\Bucket;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202505121023_disable_new_art_link_field_for_existing extends Bucket
{
    public function description(): string
    {
        return 'Disable new artifact link field for existing fields';
    }

    public function up(): void
    {
        if ($this->api->tableNameExists('plugin_tracker_field_artifact_link')) {
            $sql = <<<SQL
            INSERT INTO plugin_tracker_field_artifact_link (field_id, can_edit_reverse_links)
                SELECT tf.id, 0
                FROM tracker_field AS tf
                LEFT JOIN plugin_tracker_field_artifact_link AS ptfal ON ptfal.field_id = tf.id
                WHERE tf.formElement_type = 'art_link' AND ptfal.field_id IS NULL
            SQL;
            $this->api->dbh->exec($sql);
        }
    }
}
