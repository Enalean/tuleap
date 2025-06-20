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

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Tracker;

class SemanticProgressBuilder
{
    /**
     * @var SemanticProgressDao
     */
    private $dao;
    /**
     * @var MethodBuilder
     */
    private $method_builder;

    public function __construct(
        SemanticProgressDao $dao,
        MethodBuilder $method_builder,
    ) {
        $this->dao            = $dao;
        $this->method_builder = $method_builder;
    }

    public function getSemantic(Tracker $tracker): SemanticProgress
    {
        $row = $this->dao->searchByTrackerId($tracker->getId());
        if ($row === null) {
            return $this->getUnconfiguredSemanticProgress($tracker);
        }

        $total_effort_field_id     = $row['total_effort_field_id'];
        $remaining_effort_field_id = $row['remaining_effort_field_id'];
        $artifact_link_type        = $row['artifact_link_type'];

        if ($total_effort_field_id !== null && $remaining_effort_field_id !== null && $artifact_link_type === null) {
            return $this->buildEffortBasedSemanticProgress(
                $tracker,
                $total_effort_field_id,
                $remaining_effort_field_id
            );
        }

        if ($artifact_link_type !== null && $total_effort_field_id === null && $remaining_effort_field_id === null) {
            return $this->buildChildCountBasedSemanticProgress(
                $tracker,
                $artifact_link_type
            );
        }

        return $this->getInvalidSemanticProgress(
            $tracker,
            dgettext('tuleap-tracker', 'Progress semantic is not properly configured.')
        );
    }

    private function getUnconfiguredSemanticProgress(Tracker $tracker): SemanticProgress
    {
        return new SemanticProgress(
            $tracker,
            new MethodNotConfigured()
        );
    }

    private function getInvalidSemanticProgress(Tracker $tracker, string $error_message): SemanticProgress
    {
        return new SemanticProgress(
            $tracker,
            new InvalidMethod($error_message),
        );
    }

    private function buildEffortBasedSemanticProgress(
        Tracker $tracker,
        int $total_effort_field_id,
        int $remaining_effort_field_id,
    ): SemanticProgress {
        return new SemanticProgress(
            $tracker,
            $this->method_builder->buildMethodBasedOnEffort($tracker, $total_effort_field_id, $remaining_effort_field_id)
        );
    }

    private function buildChildCountBasedSemanticProgress(Tracker $tracker, string $artifact_link_type): SemanticProgress
    {
        return new SemanticProgress(
            $tracker,
            $this->method_builder->buildMethodBasedOnChildCount($tracker, $artifact_link_type)
        );
    }
}
