<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use PFUser;
use Tracker;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\MoveArtifactByDuckTyping;
use Tuleap\Tracker\Artifact\Artifact;

final class MoveArtifactByDuckTypingStub implements MoveArtifactByDuckTyping
{
    private int $call_count = 0;

    private function __construct(private int $limit)
    {
    }

    public static function withReturnRandomLimit(): self
    {
        return new self(random_int(1, 1000));
    }

    public function move(
        Artifact $artifact,
        Tracker $source_tracker,
        Tracker $destination_tracker,
        PFUser $user,
        DuckTypedMoveFieldCollection $field_collection,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_links_collection,
    ): int {
        $this->call_count++;

        return $this->limit;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
