<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

class b201911281504_add_project_template_xml_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description(): string
    {
        return 'Add table to record the template used for project creation';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->createTable(
            'project_template_xml',
            <<<EOT
            CREATE TABLE project_template_xml (
                id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                template_name VARCHAR(255)
            )
            EOT
        );
    }
}
