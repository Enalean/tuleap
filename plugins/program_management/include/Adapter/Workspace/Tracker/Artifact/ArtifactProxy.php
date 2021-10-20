<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class ArtifactProxy
{
    private function __construct(
        private int $id,
        private string $title,
        private ?string $status,
        private ?int $start_date,
        private ?int $end_date
    ) {
    }
    public static function buildFromArtifact(
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        LoggerInterface $logger,
        Artifact $artifact,
        \PFUser $user
    ): ?self {
        $title = $artifact->getTitle();
        if ($title === null) {
            return null;
        }

        $status       = null;
        $status_field = $artifact->getTracker()->getStatusField();
        if ($status_field !== null && $status_field->userCanRead($user)) {
            $status = $artifact->getStatus();
        }

        $semantic_timeframe = $semantic_timeframe_builder->getSemantic($artifact->getTracker());
        $time_period        = $semantic_timeframe->getTimeframeCalculator()->buildTimePeriodWithoutWeekendForArtifact(
            $artifact,
            $user,
            $logger
        );

        return new self(
            $artifact->getId(),
            $title,
            $status,
            $time_period->getStartDate(),
            $time_period->getEndDate()
        );
    }
    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getStartDate(): ?int
    {
        return $this->start_date;
    }

    public function getEndDate(): ?int
    {
        return $this->end_date;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
