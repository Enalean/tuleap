<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

use Tuleap\ForgeUpgrade\Bucket;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202410301255_expert_mode_by_default extends Bucket
{
    public function description(): string
    {
        return 'Expert mode is true by default at widget creation in plugin_crosstracker_report.';
    }

    public function up(): void
    {
        if (! $this->api->columnNameExists('plugin_crosstracker_report', 'expert_mode')) {
            return;
        }

        $sql = <<<SQL
        ALTER TABLE plugin_crosstracker_report
        MODIFY COLUMN expert_mode BOOL NOT NULL DEFAULT true
        SQL;

        $this->api->dbh->exec($sql);
    }
}
