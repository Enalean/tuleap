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
    /**
     * @var string
     */
    public $tracker_semantic_admin_url;
    /**
     * @var string
     */
    public $semantic_usages_description;
    /**
     * @var bool
     */
    public $is_semantic_defined;
    /**
     * @var string
     */
    public $updater_url;
    /**
     * @var string
     */
    public $current_method;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var array
     */
    public $total_effort_options;
    /**
     * @var array
     */
    public $remaining_effort_options;
    /**
     * @var array
     */
    public $available_computation_methods;
    /**
     * @var bool
     */
    public $is_method_effort_based;
    /**
     * @var bool
     */
    public $is_method_links_count_based;
    /**
     * @var bool
     */
    public $has_a_link_field;
    /**
     * @var string
     */
    public $tracker_fields_admin_url;

    public function __construct(
        \Tracker $tracker,
        string $semantic_usages_description,
        bool $is_semantic_defined,
        string $updater_url,
        string $current_method,
        \CSRFSynchronizerToken $csrf_token,
        array $total_effort_options,
        array $remaining_effort_options,
        array $available_computation_methods,
        bool $has_a_link_field,
    ) {
        $this->semantic_usages_description   = $semantic_usages_description;
        $this->is_semantic_defined           = $is_semantic_defined;
        $this->updater_url                   = $updater_url;
        $this->current_method                = $current_method;
        $this->csrf_token                    = $csrf_token;
        $this->total_effort_options          = $total_effort_options;
        $this->remaining_effort_options      = $remaining_effort_options;
        $this->available_computation_methods = $available_computation_methods;
        $this->is_method_effort_based        = $current_method === MethodBasedOnEffort::getMethodName();
        $this->is_method_links_count_based   = $current_method === MethodBasedOnLinksCount::getMethodName();
        $this->has_a_link_field              = $has_a_link_field;
        $this->tracker_semantic_admin_url    = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func' => 'admin-semantic',
            ]
        );
        $this->tracker_fields_admin_url      = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func' => 'admin-formElements',
            ]
        );
    }
}
