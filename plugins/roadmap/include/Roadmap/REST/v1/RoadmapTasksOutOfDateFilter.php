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

namespace Tuleap\Roadmap\REST\v1;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;

class RoadmapTasksOutOfDateFilter
{
    /**
     * @var SemanticStatusRetriever
     */
    private $semantic_status_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(SemanticStatusRetriever $semantic_status_retriever, LoggerInterface $logger)
    {
        $this->semantic_status_retriever = $semantic_status_retriever;
        $this->logger                    = $logger;
    }

    /**
     * @param Artifact[] $artifacts
     * @return Artifact[]
     */
    public function filterOutOfDateArtifacts(array $artifacts, \Tracker $tracker, DateTimeImmutable $now): array
    {
        $semantic_status = $this->semantic_status_retriever->retrieveSemantic($tracker);
        return array_filter($artifacts, function ($artifact) use ($semantic_status, $now) {
            return ! $this->hasBeenClosedMoreThanOneYearAgo($artifact, $semantic_status, $now);
        });
    }

    private function hasBeenClosedMoreThanOneYearAgo(Artifact $artifact, \Tracker_Semantic_Status $semantic_status, DateTimeImmutable $now): bool
    {
        $status_field = $semantic_status->getField();
        if ($status_field === null) {
            return false;
        }

        if ($semantic_status->isOpen($artifact)) {
            return false;
        }

        $changesets = array_reverse($artifact->getChangesets());
        foreach ($changesets as $changeset) {
            if (! $changeset->canHoldValue()) {
                continue;
            }

            $status_value = $changeset->getValue($status_field);
            if ($status_value === null) {
                continue;
            }

            if (! $status_value->hasChanged()) {
                continue;
            }

            $value = $status_value->getValue();
            if ($value === null) {
                continue;
            }

            if ($this->isAnOpenValue((int) $value[0], $semantic_status)) {
                continue;
            }

            $close_date   = (new DateTimeImmutable())->setTimestamp((int) $changeset->getSubmittedOn());
            $closed_since = $now->diff($close_date);
            if ($closed_since->days > 365) {
                return true;
            }

            return false;
        }

        $this->logger->error(
            sprintf(
                "[Roadmap widget] Artifact #%s is closed but we can't find the changeset where the action has been performed. Hence, it won't be displayed.",
                $artifact->getId()
            )
        );
        return true;
    }

    private function isAnOpenValue(int $value_id, Tracker_Semantic_Status $semantic_status): bool
    {
        return in_array($value_id, $semantic_status->getOpenValues());
    }
}
