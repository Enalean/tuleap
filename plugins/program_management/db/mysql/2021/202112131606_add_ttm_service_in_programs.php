<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202112131606_add_ttm_service_in_programs extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add the TTM service in projects created from ART templates';
    }

    public function up(): void
    {
        $sql = 'INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
                  SELECT DISTINCT group_id, "plugin_testmanagement:service_lbl_key", "plugin_testmanagement:service_desc_key", "plugin_testmanagement", "/plugins/testmanagement/?group_id=$group_id", 1, 0, "system", 250
                  FROM service
                  INNER JOIN project_template_xml ON (service.group_id = project_template_xml.id AND template_name = "program_management_program")
                  WHERE group_id NOT IN (
                    SELECT group_id FROM service WHERE short_name = "plugin_testmanagement"
                )';

        $this->api->dbh->exec($sql);
    }
}
