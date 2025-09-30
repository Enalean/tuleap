<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
final class b202210101010_add_missing_indexes extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add missing indexes in baseline tables';
    }

    public function up(): void
    {
        $this->api->addIndex(
            'plugin_baseline_role_assignment',
            'idx_project',
            'ALTER TABLE plugin_baseline_role_assignment ADD INDEX idx_project(project_id, role(11))'
        );

        $this->api->addIndex(
            'plugin_baseline_baseline',
            'idx_artifact',
            'ALTER TABLE plugin_baseline_baseline ADD INDEX idx_artifact(artifact_id)'
        );

        $this->api->addIndex(
            'plugin_baseline_comparison',
            'idx_base',
            'ALTER TABLE plugin_baseline_comparison ADD INDEX idx_base(base_baseline_id)'
        );

        $this->api->addIndex(
            'plugin_baseline_comparison',
            'idx_compare',
            'ALTER TABLE plugin_baseline_comparison ADD INDEX idx_compare(compared_to_baseline_id)'
        );
    }
}
