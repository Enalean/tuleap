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
use Tuleap\Reference\ByNature\Forum\CrossReferenceForumOrganizer;
use Tuleap\Reference\ByNature\FRS\CrossReferenceFRSOrganizer;
use Tuleap\Reference\ByNature\News\CrossReferenceNewsOrganizer;
use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceByNatureInCoreOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceWikiOrganizer
     */
    private $wiki_organizer;
    /**
     * @var CrossReferenceByNatureInCoreOrganizer
     */
    private $core_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $by_nature_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceFRSOrganizer
     */
    private $frs_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceForumOrganizer
     */
    private $forum_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceNewsOrganizer
     */
    private $news_organizer;

    public function setUp(): void
    {
        $this->by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $this->wiki_organizer      = Mockery::mock(CrossReferenceWikiOrganizer::class);
        $this->frs_organizer       = Mockery::mock(CrossReferenceFRSOrganizer::class);
        $this->forum_organizer     = Mockery::mock(CrossReferenceForumOrganizer::class);
        $this->news_organizer      = Mockery::mock(CrossReferenceNewsOrganizer::class);

        $this->core_organizer = new CrossReferenceByNatureInCoreOrganizer(
            $this->wiki_organizer,
            $this->frs_organizer,
            $this->forum_organizer,
            $this->news_organizer
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

    public function testItOrganizesFRSReleaseReferences(): void
    {
        $release_ref = CrossReferencePresenterBuilder::get(1)->withType('release')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $release_ref,
                ],
            ]
        );

        $this->frs_organizer
            ->shouldReceive('organizeFRSReleaseReference')
            ->with($release_ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesFRSFileReferences(): void
    {
        $file_ref = CrossReferencePresenterBuilder::get(1)->withType('file')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $file_ref,
                ],
            ]
        );

        $this->frs_organizer
            ->shouldReceive('organizeFRSFileReference')
            ->with($file_ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesForumMessagesReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('forum_message')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $ref,
                ],
            ]
        );

        $this->forum_organizer
            ->shouldReceive('organizeMessageReference')
            ->with($ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesForumReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('forum')->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $ref,
                ],
            ]
        );

        $this->forum_organizer
            ->shouldReceive('organizeForumReference')
            ->with($ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesNewsReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(2)->withType(\ReferenceManager::REFERENCE_NATURE_NEWS)->build();

        $this->by_nature_organizer->shouldReceive(
            [
                'getCrossReferencePresenters' => [
                    CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
                    $ref,
                ],
            ]
        );

        $this->news_organizer
            ->shouldReceive('organizeNewsReference')
            ->with($ref, $this->by_nature_organizer)
            ->once();

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }
}
