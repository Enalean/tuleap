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
final class b202309110933_remove_abbr_in_split_modal_preference extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Remove abbreviation in split modal preference";
    }

    public function up(): void
    {
        $this->api->dbh->exec(
            "INSERT INTO user_preferences(user_id, preference_name, preference_value)
            SELECT user_id, 'should_display_agiledashboard_split_modal', preference_value
            FROM user_preferences
            WHERE preference_name = 'should_display_ad_split_modal'"
        );

        $this->api->dbh->exec(
            "DELETE FROM user_preferences WHERE preference_name = 'should_display_ad_split_modal'"
        );
    }
}
