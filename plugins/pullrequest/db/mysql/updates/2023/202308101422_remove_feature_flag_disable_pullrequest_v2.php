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
final class b202308101422_remove_feature_flag_disable_pullrequest_v2 extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Remove old feature flag named "feature_flag_allow_pullrequest_v2" from forgeconfig.';
    }

    public function up(): void
    {
        $this->api->dbh->exec("DELETE FROM forgeconfig WHERE name='feature_flag_disable_pullrequest_v2'");
    }
}
