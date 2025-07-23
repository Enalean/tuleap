<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\SearchReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\StoredLinkRow;

final class SearchReverseLinksStub implements SearchReverseLinks
{
    /**
     * @param StoredLinkRow[] $rows
     */
    private function __construct(private array $rows)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withRows(StoredLinkRow $first_row, StoredLinkRow ...$other_rows): self
    {
        return new self([$first_row, ...$other_rows]);
    }

    public static function withNoRows(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchReverseLinksById(int $artifact_id): array
    {
        return $this->rows;
    }
}
