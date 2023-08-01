<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Home;

/**
 * @psalm-immutable
 */
final class KanbanHomePresenter
{
    public readonly bool $has_at_least_one_kanban;
    public readonly bool $are_trackers_available;

    /**
     * @param KanbanSummaryPresenter[] $kanban_summary_presenters
     * @param list<array{id: int, name: string, used: bool}> $trackers
     */
    public function __construct(
        public readonly array $kanban_summary_presenters,
        public readonly bool $is_admin,
        public readonly array $trackers,
        public readonly string $create_kanban_url,
        public readonly \Tuleap\CSRFSynchronizerTokenPresenter $csrf_token,
    ) {
        $this->has_at_least_one_kanban = count($this->kanban_summary_presenters) > 0;
        $this->are_trackers_available  = $this->areTrackersAvailable();
    }

    private function areTrackersAvailable(): bool
    {
        foreach ($this->trackers as $tracker) {
            if ($tracker['used'] === false) {
                return true;
            }
        }

        return false;
    }
}
