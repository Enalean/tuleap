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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;

class SemanticTimeframeAdministrationPresenter
{
    public SemanticTimeframeCurrentConfigurationPresenter $configuration_presenter;

    public string $csrf_token;
    public string $target_url;
    public string $tracker_semantic_admin_url;
    public ?string $usable_date_fields;
    public ?string $usable_numeric_fields;
    public ?string $suitable_trackers;

    public int $current_tracker_id;
    public ?int $start_date_field_id;
    public ?int $duration_field_id;
    public ?int $end_date_field_id;
    public ?int $implied_from_tracker_id;

    public bool $has_other_trackers_implying_their_timeframes;
    public bool $has_tracker_charts;
    public bool $has_artifact_link_field;

    public function __construct(
        \CSRFSynchronizerToken $csrf,
        \Tracker $tracker,
        string $target_url,
        bool $has_tracker_charts,
        bool $has_artifact_link_field,
        array $usable_date_fields,
        array $usable_numeric_fields,
        IComputeTimeframes $timeframe,
        SemanticTimeframeCurrentConfigurationPresenter $configuration_presenter,
        array $suitable_trackers,
        public string $semantic_presentation,
        public readonly bool $should_send_event_in_notification,
    ) {
        $start_date_field     = $timeframe->getStartDateField();
        $end_date_field       = $timeframe->getEndDateField();
        $duration_field       = $timeframe->getDurationField();
        $implied_from_tracker = $timeframe->getTrackerFromWhichTimeframeIsImplied();

        $this->configuration_presenter                      = $configuration_presenter;
        $this->usable_date_fields                           = \json_encode($usable_date_fields);
        $this->usable_numeric_fields                        = \json_encode($usable_numeric_fields);
        $this->suitable_trackers                            = \json_encode($suitable_trackers);
        $this->start_date_field_id                          = ($start_date_field !== null) ? $start_date_field->getId() : 0;
        $this->end_date_field_id                            = ($end_date_field !== null) ? $end_date_field->getId() : 0;
        $this->duration_field_id                            = ($duration_field !== null) ? $duration_field->getId() : 0;
        $this->implied_from_tracker_id                      = ($implied_from_tracker !== null) ? $implied_from_tracker->getId() : 0;
        $this->target_url                                   = $target_url;
        $this->csrf_token                                   = $csrf->getToken();
        $this->has_other_trackers_implying_their_timeframes = $configuration_presenter->are_semantics_implied_from_current_tracker;
        $this->has_tracker_charts                           = $has_tracker_charts;
        $this->has_artifact_link_field                      = $has_artifact_link_field;
        $this->current_tracker_id                           = $tracker->getId();

        $this->tracker_semantic_admin_url = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => $tracker->getId(),
                'func' => 'admin-semantic',
            ]
        );
    }
}
