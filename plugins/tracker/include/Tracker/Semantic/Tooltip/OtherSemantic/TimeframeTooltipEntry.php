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

namespace Tuleap\Tracker\Semantic\Tooltip\OtherSemantic;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Tooltip\OtherSemanticTooltipEntryFetcher;

final class TimeframeTooltipEntry implements OtherSemanticTooltipEntryFetcher
{
    public function __construct(
        private readonly SemanticTimeframeBuilder $semantic_timeframe_builder,
        private readonly \TemplateRendererFactory $renderer_factory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchTooltipEntry(Artifact $artifact, \PFUser $user): string
    {
        $semantic_timeframe = $this->semantic_timeframe_builder->getSemantic($artifact->getTracker());
        if (! $this->canSemanticBeUsed($semantic_timeframe, $user)) {
            return '';
        }

        $time_period = $semantic_timeframe
            ->getTimeframeCalculator()
            ->buildTimePeriodWithoutWeekendForArtifactForREST($artifact, $user, $this->logger);

        $start_date = $time_period->getStartDate();
        $end_date   = $time_period->getEndDate();

        if ($start_date === null && $end_date === null) {
            return '';
        }

        $start = $start_date ? (new \DateTimeImmutable())->setTimestamp($start_date) : null;
        $end   = $end_date ? (new \DateTimeImmutable())->setTimestamp($end_date) : null;

        $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../../templates/tooltip/other-semantic/');

        return $renderer->renderToString('timeframe-tooltip-entry', [
            'start_date'                => $start ? $start->format("F j, Y") : '', //TODO i18n
            'end_date'                  => $end ? $end->format("F j, Y") : '',
            'is_end_date_in_error'      => ! $this->haveEndDateGreaterOrEqualToStartDate($start, $end),
            'time_period_error_message' => $time_period->getErrorMessage(),
        ]);
    }

    private function haveEndDateGreaterOrEqualToStartDate(?\DateTimeImmutable $start, ?\DateTimeImmutable $end): bool
    {
        if ($start === null && $end === null) {
            return false;
        }

        return ! $start || ! $end || $end >= $start;
    }

    private function canSemanticBeUsed(SemanticTimeframe $semantic_timeframe, \PFUser $user): bool
    {
        if (! $semantic_timeframe->isDefined()) {
            return false;
        }

        $start_date_field = $semantic_timeframe->getStartDateField();
        if ($start_date_field && ! $start_date_field->userCanRead($user)) {
            return false;
        }

        $end_date_field = $semantic_timeframe->getEndDateField();
        if ($end_date_field && ! $end_date_field->userCanRead($user)) {
            return false;
        }

        $duration_field = $semantic_timeframe->getDurationField();
        if ($duration_field && ! $duration_field->userCanRead($user)) {
            return false;
        }

        return true;
    }
}
