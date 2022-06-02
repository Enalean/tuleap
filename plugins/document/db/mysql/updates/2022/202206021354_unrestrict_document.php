<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
class b202206021354_unrestrict_document extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Remove restrictions on Document plugin";
    }

    public function up(): void
    {
        $res = $this->api->dbh->exec("UPDATE plugin SET prj_restricted = 0 WHERE name = 'document'");
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                "Unable to unrestrict the plugin Document"
            );
        }

        $res = $this->api->dbh->exec(
            "DELETE project_plugin.*
                FROM project_plugin
                    INNER JOIN plugin ON (project_plugin.plugin_id = plugin.id)
                WHERE plugin.name = 'document'"
        );
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                "Unable to unrestrict the plugin Document"
            );
        }
    }
}
