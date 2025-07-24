<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveArtifactLinkField;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveArtifactLinkFieldStub implements RetrieveArtifactLinkField
{
    /**
     * @param ArtifactLinkFieldReference[] $artifact_links
     */
    private function __construct(private bool $should_throw, private array $artifact_links)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withFields(
        ArtifactLinkFieldReference $field,
        ArtifactLinkFieldReference ...$other_fields,
    ): self {
        return new self(false, [$field, ...$other_fields]);
    }

    public static function withError(): self
    {
        return new self(true, []);
    }

    #[\Override]
    public function getArtifactLinkField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): ArtifactLinkFieldReference
    {
        if ($this->should_throw) {
            throw new NoArtifactLinkFieldException($tracker_identifier);
        }
        if (count($this->artifact_links) > 0) {
            return array_shift($this->artifact_links);
        }
        throw new \LogicException('No artifact link field configured');
    }
}
