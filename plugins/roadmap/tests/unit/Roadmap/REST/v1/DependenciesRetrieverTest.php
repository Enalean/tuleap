<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Tuleap\Roadmap\NatureForRoadmapDao;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DependenciesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetDependencies(): void
    {
        $dao = $this->createMock(NatureForRoadmapDao::class);
        $dao
            ->method('searchForwardLinksHavingSemantics')
            ->willReturn([
                ['nature' => 'depends_on', 'id' => 124],
                ['nature' => 'fixed_in', 'id' => 234],
                ['nature' => 'depends_on', 'id' => 125],
                ['nature' => '', 'id' => 111],
                ['nature' => '', 'id' => 666],
                ['nature' => 'fixed_in', 'id' => 234],
            ]);

        $retriever = new DependenciesRetriever($dao);

        $dependencies = $retriever->getDependencies(new Artifact(123, 101, 1001, 123456789, false));

        self::assertEquals(
            [
                new DependenciesByNature('depends_on', [124, 125]),
                new DependenciesByNature('fixed_in', [234]),
                new DependenciesByNature('', [111, 666]),
            ],
            $dependencies,
        );
    }
}
