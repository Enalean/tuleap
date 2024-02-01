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

use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use Tuleap\Reference\ByNature\Forum\CrossReferenceForumOrganizer;
use Tuleap\Reference\ByNature\FRS\CrossReferenceFRSOrganizer;
use Tuleap\Reference\ByNature\News\CrossReferenceNewsOrganizer;
use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceByNatureInCoreOrganizerTest extends TestCase
{
    private CrossReferenceWikiOrganizer&MockObject $wiki_organizer;
    private CrossReferenceByNatureInCoreOrganizer $core_organizer;
    private CrossReferenceByNatureOrganizer&MockObject $by_nature_organizer;
    private CrossReferenceFRSOrganizer&MockObject $frs_organizer;
    private CrossReferenceForumOrganizer&MockObject $forum_organizer;
    private CrossReferenceNewsOrganizer&MockObject $news_organizer;

    public function setUp(): void
    {
        $this->by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $this->wiki_organizer      = $this->createMock(CrossReferenceWikiOrganizer::class);
        $this->frs_organizer       = $this->createMock(CrossReferenceFRSOrganizer::class);
        $this->forum_organizer     = $this->createMock(CrossReferenceForumOrganizer::class);
        $this->news_organizer      = $this->createMock(CrossReferenceNewsOrganizer::class);

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

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $wiki_ref,
        ]);

        $this->wiki_organizer
            ->expects(self::once())
            ->method('organizeWikiReference')
            ->with($wiki_ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesFRSReleaseReferences(): void
    {
        $release_ref = CrossReferencePresenterBuilder::get(1)->withType('release')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $release_ref,
        ]);

        $this->frs_organizer
            ->expects(self::once())
            ->method('organizeFRSReleaseReference')
            ->with($release_ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesFRSFileReferences(): void
    {
        $file_ref = CrossReferencePresenterBuilder::get(1)->withType('file')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $file_ref,
        ]);

        $this->frs_organizer
            ->expects(self::once())
            ->method('organizeFRSFileReference')
            ->with($file_ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesForumMessagesReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('forum_message')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $ref,
        ]);

        $this->forum_organizer
            ->expects(self::once())
            ->method('organizeMessageReference')
            ->with($ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesForumReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->withType('forum')->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $ref,
        ]);

        $this->forum_organizer
            ->expects(self::once())
            ->method('organizeForumReference')
            ->with($ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }

    public function testItOrganizesNewsReferences(): void
    {
        $ref = CrossReferencePresenterBuilder::get(2)->withType(ReferenceManager::REFERENCE_NATURE_NEWS)->build();

        $this->by_nature_organizer->method('getCrossReferencePresenters')->willReturn([
            CrossReferencePresenterBuilder::get(1)->withType('git')->build(),
            $ref,
        ]);

        $this->news_organizer
            ->expects(self::once())
            ->method('organizeNewsReference')
            ->with($ref, $this->by_nature_organizer);

        $this->core_organizer->organizeCoreReferences($this->by_nature_organizer);
    }
}
