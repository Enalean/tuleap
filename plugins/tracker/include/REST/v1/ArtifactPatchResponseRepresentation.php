<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tuleap\Tracker\Action\Move\FeedbackFieldCollector;

/**
 * @psalm-immutable
 */
class ArtifactPatchResponseRepresentation
{

    /**
     * @var ArtifactPatchDryRunResponseRepresentation | null {@type ArtifactPatchDryRunResponseRepresentation}
     */
    public $dry_run;

    private function __construct(?ArtifactPatchDryRunResponseRepresentation $dry_run)
    {
        $this->dry_run = $dry_run;
    }

    public static function withoutDryRun(): self
    {
        return new self(null);
    }

    public static function fromFeedbackFieldCollector(FeedbackFieldCollector $feedback_field_collector): self
    {
        $dry_run_representation = new ArtifactPatchDryRunResponseRepresentation($feedback_field_collector);

        return new self($dry_run_representation);
    }
}
