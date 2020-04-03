<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use Psr\Log\LoggerInterface;
use Tracker_Artifact;

final class WorkflowRulesManagerLoopSafeGuard
{
    /**
     * @var array<int,true>
     */
    private $already_processed_artifacts = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(Tracker_Artifact $artifact, callable $process_to_protect): void
    {
        $artifact_id = $artifact->getId();
        if (isset($this->already_processed_artifacts[$artifact_id])) {
            $this->logger->error('Incoherent configuration detected, artifact #' . $artifact->getId() . ' has already been processed');
            return;
        }
        $this->already_processed_artifacts[$artifact_id] = true;
        $process_to_protect();
        unset($this->already_processed_artifacts[$artifact_id]);
    }
}
