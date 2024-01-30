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

namespace Tuleap\Reference\ByNature\Wiki;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\PHPWiki\WikiPage;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceWikiOrganizerTest extends TestCase
{
    private WikiPageFromReferenceValueRetriever&MockObject $wiki_page_retriever;
    private CrossReferenceWikiOrganizer $wiki_organizer;
    private CrossReferenceByNatureOrganizer&MockObject $by_nature_organizer;
    private Project $project;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->project   = ProjectTestBuilder::aProject()->build();
        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($this->project);

        $this->wiki_page_retriever = $this->createMock(WikiPageFromReferenceValueRetriever::class);

        $this->wiki_organizer = new CrossReferenceWikiOrganizer(
            $project_manager,
            $this->wiki_page_retriever,
        );

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $this->by_nature_organizer->method('getCurrentUser')->willReturn($this->user);
    }

    public function testItRemovesReferenceIfWikiPageIsNotAccessible(): void
    {
        $wiki_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('wiki_page')
            ->withValue('MyWikiPage')
            ->build();

        $this->wiki_page_retriever
            ->expects(self::once())
            ->method('getWikiPageUserCanView')
            ->with($this->project, $this->user, 'MyWikiPage')
            ->willReturn(null);

        $this->by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($wiki_ref);

        $this->wiki_organizer->organizeWikiReference($wiki_ref, $this->by_nature_organizer);
    }

    public function testItAddsWikiPageToUnlabelledSection()
    {
        $wiki_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('wiki_page')
            ->withValue('MyWikiPage')
            ->build();

        $this->wiki_page_retriever
            ->expects(self::once())
            ->method('getWikiPageUserCanView')
            ->with($this->project, $this->user, 'MyWikiPage')
            ->willReturn($this->createMock(WikiPage::class));

        $this->by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with($wiki_ref, '');

        $this->wiki_organizer->organizeWikiReference($wiki_ref, $this->by_nature_organizer);
    }
}
