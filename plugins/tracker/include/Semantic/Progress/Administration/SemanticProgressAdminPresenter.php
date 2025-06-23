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

namespace Tuleap\Tracker\Semantic\Progress\Administration;

use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnLinksCount;

/**
 * @psalm-immutable
 */
final class SemanticProgressAdminPresenter
{
    public bool $is_method_effort_based;
    public bool $is_method_links_count_based;
    public string $tracker_fields_admin_url;
    public string $tracker_semantic_admin_url;

    public function __construct(
        \Tuleap\Tracker\Tracker $tracker,
        public string $semantic_usages_description,
        public bool $is_semantic_defined,
        public string $updater_url,
        public string $current_method,
        public \CSRFSynchronizerToken $csrf_token,
        public array $total_effort_options,
        public array $remaining_effort_options,
        public array $available_computation_methods,
        public bool $has_a_link_field,
    ) {
        $this->is_method_effort_based      = $current_method === MethodBasedOnEffort::getMethodName();
        $this->is_method_links_count_based = $current_method === MethodBasedOnLinksCount::getMethodName();
        $this->tracker_semantic_admin_url  = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func' => 'admin-semantic',
            ]
        );
        $this->tracker_fields_admin_url    = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func' => 'admin-formElements',
            ]
        );
    }
}
