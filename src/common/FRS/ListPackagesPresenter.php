<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Project;

/**
 * @psalm-immutable
 */
final readonly class ListPackagesPresenter
{
    public bool $has_packages;
    public string $add_url;
    public bool $should_display_stats;

    /**
     * @param list<PackagePresenter> $packages
     */
    public function __construct(
        Project $project,
        public array $packages,
        public ProjectStatsPresenter $stats,
        public bool $can_admin,
        public \CSRFSynchronizerToken $csrf_token,
    ) {
        $this->has_packages         = count($packages) > 0;
        $this->should_display_stats = $stats->size > 0;

        $this->add_url = '/file/admin/package.php?' . http_build_query([
            'func' => 'add',
            'group_id' => $project->getID(),
        ]);
    }
}
