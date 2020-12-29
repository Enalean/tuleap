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

namespace Tuleap\Reference\ByNature;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Reference\ByNature\ConcurrentVersionsSystem\CrossReferenceCvsOrganizer;
use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceByNatureInCoreOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceWikiOrganizer
     */
    private $wiki_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceCvsOrganizer
     */
    private $cvs_organizer;
    /**
     * @var CrossReferenceByNatureInCoreOrganizer
     */
    private $core_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $by_nature_organizer;

    public function setUp(): void
    {
        $this->by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $this->wiki_organizer      = Mockery::mock(CrossReferenceWikiOrganizer::class);
        $this->cvs_organizer       = Mockery::mock(CrossReferenceCvsOrganizer::class);

        $this->core_organizer = new CrossReferenceByNatureInCoreOrganizer(
            $this->wiki_organizer,
            $this->cvs_organizer,
        );
    }

    public function testItOrganizeWikiReferences(): void
    {
        $wiki_ref = CrossReferencePresenterBuilder::get(1)->withType('wiki_page')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $wiki_ref,
                ],
            ]
        );

        $this->wiki_organizer
            ->shouldReceive('organizeWikiReference')
            ->with($wiki_ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizeCvsReferences(): void
    {
        $cvs_ref = CrossReferencePresenterBuilder::get(1)->withType('cvs_commit')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $cvs_ref,
                ],
            ]
        );

        $this->cvs_organizer
            ->shouldReceive('organizeCvsReference')
            ->with($cvs_ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }
}
