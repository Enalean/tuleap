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
final class b202109151500_add_bot_project_id extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add project_id information in plugin_botmattermost_bot table';
    }

    public function up(): void
    {
        if (! $this->api->columnNameExists('plugin_botmattermost_bot', 'project_id')) {
            $this->api->dbh->exec('ALTER TABLE plugin_botmattermost_bot ADD COLUMN project_id INT(11) DEFAULT NULL');
        }

        $this->api->addIndex(
            'plugin_botmattermost_bot',
            'idx_project_id',
            'ALTER TABLE plugin_botmattermost_bot ADD INDEX idx_project_id (project_id)'
        );
    }
}
