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

use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;

class SemanticTimeframeCurrentConfigurationPresenterBuilder
{
    private \Tuleap\Tracker\Tracker $current_tracker;

    private SemanticTimeframeDao $dao;

    private IComputeTimeframes $i_compute_timeframes;

    private \TrackerFactory $tracker_factory;

    public function __construct(
        \Tuleap\Tracker\Tracker $current_tracker,
        IComputeTimeframes $i_compute_timeframes,
        SemanticTimeframeDao $dao,
        \TrackerFactory $tracker_factory,
    ) {
        $this->current_tracker      = $current_tracker;
        $this->i_compute_timeframes = $i_compute_timeframes;
        $this->dao                  = $dao;
        $this->tracker_factory      = $tracker_factory;
    }

    public function build(): SemanticTimeframeCurrentConfigurationPresenter
    {
        return new SemanticTimeframeCurrentConfigurationPresenter(
            $this->i_compute_timeframes->getConfigDescription(),
            $this->getSemanticsImpliedFromCurrentTracker(),
            $this->getTrackerFromWhichWeImplyTheSemantic()
        );
    }

    private function getSemanticsImpliedFromCurrentTracker(): array
    {
        if ($this->i_compute_timeframes->getName() === TimeframeImpliedFromAnotherTracker::NAME) {
            return [];
        }

        $semantics_implied_from_tracker = $this->dao->getSemanticsImpliedFromGivenTracker($this->current_tracker->getId());
        if (! $semantics_implied_from_tracker) {
            return [];
        }

        $semantics_links = [];
        foreach ($semantics_implied_from_tracker as $semantic_data) {
            $tracker = $this->tracker_factory->getTrackerById($semantic_data['tracker_id']);

            if ($tracker === null) {
                continue;
            }

            $semantics_links[] = [
                'tracker_name' => $tracker->getName(),
                'tracker_semantic_timeframe_admin_url' => TRACKER_BASE_URL . '/?' . http_build_query(
                    [
                        'tracker' => $tracker->getId(),
                        'func' => 'admin-semantic',
                        'semantic' => 'timeframe',
                    ]
                ),
            ];
        }

        return $semantics_links;
    }

    private function getTrackerFromWhichWeImplyTheSemantic(): ?\Tuleap\Tracker\Tracker
    {
        if ($this->i_compute_timeframes->getName() !== TimeframeImpliedFromAnotherTracker::NAME) {
            return null;
        }

        $config = $this->dao->searchByTrackerId($this->current_tracker->getId());
        if ($config === null || ! isset($config['implied_from_tracker_id'])) {
            return null;
        }

        return $this->tracker_factory->getTrackerById($config['implied_from_tracker_id']);
    }
}
