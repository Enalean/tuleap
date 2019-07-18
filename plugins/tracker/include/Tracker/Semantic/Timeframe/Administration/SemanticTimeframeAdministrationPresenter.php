<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

class SemanticTimeframeAdministrationPresenter
{
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf;

    /**
     * @var array
     */
    public $usable_date_fields;

    /**
     * @var array
     */
    public $usable_numeric_fields;

    /**
     * @var \Tracker_FormElement_Field_Date|null
     */
    public $start_date_field;

    /**
     * @var \Tracker_FormElement_Field_Numeric|null
     */
    public $duration_field;

    /**
     * @var string
     */
    public $tracker_semantic_admin_url;

    /**
     * @var bool
     */
    public $is_semantic_configured;

    /**
     * @var string
     */
    public $target_url;

    /**
     * @var bool
     */
    public $has_tracker_charts;

    public function __construct(
        \CSRFSynchronizerToken $csrf,
        \Tracker $tracker,
        string $target_url,
        bool $has_tracker_charts,
        array $usable_date_fields,
        array $usable_numeric_fields,
        ?\Tracker_FormElement_Field_Date $start_date_field,
        ?\Tracker_FormElement_Field_Numeric $duration_field
    ) {
        $this->csrf                       = $csrf;
        $this->usable_date_fields         = $usable_date_fields;
        $this->start_date_field           = $start_date_field;
        $this->duration_field             = $duration_field;
        $this->usable_numeric_fields      = $usable_numeric_fields;
        $this->is_semantic_configured     = $start_date_field !== null && $duration_field !== null;
        $this->has_tracker_charts         = $has_tracker_charts;
        $this->target_url                 = $target_url;
        $this->tracker_semantic_admin_url = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func'    => 'admin-semantic'
            ]
        );
    }
}
