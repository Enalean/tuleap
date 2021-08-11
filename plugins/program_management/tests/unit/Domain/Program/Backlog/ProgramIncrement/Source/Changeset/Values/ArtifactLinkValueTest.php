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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxArtifactLinkType;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;

final class ArtifactLinkValueTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMEBOX_ID = 90;

    public function testItBuildsFromReplicationData(): void
    {
        $replication_data = ReplicationDataBuilder::buildWithArtifactId(self::TIMEBOX_ID);
        $value            = ArtifactLinkValue::fromReplicationData($replication_data);
        self::assertEquals([
            'new_values' => (string) self::TIMEBOX_ID,
            'natures'    => [(string) self::TIMEBOX_ID => TimeboxArtifactLinkType::ART_LINK_SHORT_NAME]
        ], $value->getValues());
    }
}
