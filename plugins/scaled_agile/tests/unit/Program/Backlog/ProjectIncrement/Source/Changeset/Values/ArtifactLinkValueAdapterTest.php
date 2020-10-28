<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values;

use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\ProjectIncrementArtifactLinkType;
use Tuleap\Tracker\Artifact\Artifact;

final class ArtifactLinkValueAdapterTest extends TestCase
{
    public function testItBuildsArtifactLinkData(): void
    {
        $source_artifact    = new Artifact(101, 1, 102, 123456789, true);
        $adapter = new ArtifactLinkValueAdapter();
        $artifact_link_data = $adapter->build($source_artifact);

        $expected_value = [
            'new_values' => "101",
            'natures'    => ["101" => ProjectIncrementArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
        $this->assertEquals($expected_value, $artifact_link_data->getValues());
    }
}
