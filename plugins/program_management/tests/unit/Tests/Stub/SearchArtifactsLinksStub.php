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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;

final class SearchArtifactsLinksStub implements SearchArtifactsLinks
{
    private function __construct(private array $artifact_links)
    {
    }

    public static function build(): self
    {
        return new self([]);
    }

    public function withArtifactsLinkedToFeature(int $feature_id, array $artifact_links): self
    {
        $this->artifact_links[$feature_id] = $artifact_links;
        return $this;
    }

    #[\Override]
    public function getArtifactsLinkedToId(int $artifact_id, int $program_increment_id): array
    {
        return $this->artifact_links[$artifact_id] ?? [];
    }
}
