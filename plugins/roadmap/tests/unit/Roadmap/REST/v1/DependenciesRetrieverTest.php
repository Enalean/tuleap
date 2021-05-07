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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

final class DependenciesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetDependencies(): void
    {
        $dao = Mockery::mock(NatureDao::class, [
            'searchForwardNatureShortNamesForGivenArtifact' => [
                ['shortname' => 'depends_on'],
                ['shortname' => 'fixed_in'],
                ['shortname' => ''],
            ],
        ]);
        $dao->shouldReceive('getForwardLinkedArtifactIds')
            ->with(123, 'depends_on', PHP_INT_MAX, 0)
            ->andReturn([124, 125]);
        $dao->shouldReceive('getForwardLinkedArtifactIds')
            ->with(123, 'fixed_in', PHP_INT_MAX, 0)
            ->andReturn([234]);
        $dao->shouldReceive('getForwardLinkedArtifactIds')
            ->with(123, '', PHP_INT_MAX, 0)
            ->andReturn([111, 666]);

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
