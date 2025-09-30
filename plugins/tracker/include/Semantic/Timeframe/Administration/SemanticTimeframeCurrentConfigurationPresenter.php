<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

/**
 * @psalm-immutable
 */
final class SemanticTimeframeCurrentConfigurationPresenter
{
    public string $current_config_description;

    public array $semantics_implied_from_current_tracker;

    public bool $are_semantics_implied_from_current_tracker;

    public bool $is_semantic_implied;

    public string $tracker_from_which_we_imply_the_semantic_admin_url;

    public string $tracker_name_from_which_we_imply_the_semantic = '';

    public function __construct(
        string $current_config_description,
        array $semantics_implied_from_current_tracker,
        ?\Tuleap\Tracker\Tracker $tracker_from_which_we_imply_the_semantic,
    ) {
        $this->current_config_description                         = $current_config_description;
        $this->semantics_implied_from_current_tracker             = $semantics_implied_from_current_tracker;
        $this->are_semantics_implied_from_current_tracker         = count($semantics_implied_from_current_tracker) > 0;
        $this->is_semantic_implied                                = $tracker_from_which_we_imply_the_semantic !== null;
        $this->tracker_from_which_we_imply_the_semantic_admin_url = $this->getTrackerSemanticTimeframeAdminUrl($tracker_from_which_we_imply_the_semantic);

        if ($tracker_from_which_we_imply_the_semantic !== null) {
            $this->tracker_name_from_which_we_imply_the_semantic = $tracker_from_which_we_imply_the_semantic->getName();
        }
    }

    private function getTrackerSemanticTimeframeAdminUrl(?\Tuleap\Tracker\Tracker $tracker_from_which_we_imply_the_semantic): string
    {
        if ($tracker_from_which_we_imply_the_semantic === null) {
            return '';
        }

        return TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker_from_which_we_imply_the_semantic->getId(),
                'func' => 'admin-semantic',
                'semantic' => 'timeframe',
            ]
        );
    }
}
