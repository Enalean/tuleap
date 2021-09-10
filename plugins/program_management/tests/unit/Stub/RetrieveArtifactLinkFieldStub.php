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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveArtifactLinkField;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;

final class RetrieveArtifactLinkFieldStub implements RetrieveArtifactLinkField
{
    /**
     * @var ArtifactLinkFieldReference[]
     */
    private array $artifact_links;

    private function __construct(ArtifactLinkFieldReference ...$artifact_links)
    {
        $this->artifact_links = $artifact_links;
    }

    public static function withFields(ArtifactLinkFieldReference ...$artifact_links): self
    {
        return new self(...$artifact_links);
    }

    public function getArtifactLinkField(TrackerIdentifier $tracker_identifier, ?ConfigurationErrorsCollector $errors_collector): ArtifactLinkFieldReference
    {
        if (count($this->artifact_links) > 0) {
            return array_shift($this->artifact_links);
        }
        throw new \LogicException('No artifact link field configured');
    }
}
