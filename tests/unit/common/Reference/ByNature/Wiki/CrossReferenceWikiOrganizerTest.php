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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use Tuleap\PHPWiki\WikiPage;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceWikiOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|WikiPageFromReferenceValueRetriever
     */
    private $wiki_page_retriever;
    /**
     * @var CrossReferenceWikiOrganizer
     */
    private $wiki_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $by_nature_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->project   = Mockery::mock(Project::class);
        $project_manager = Mockery::mock(\ProjectManager::class);
        $project_manager->shouldReceive(['getProject' => $this->project]);

        $this->wiki_page_retriever = Mockery::mock(WikiPageFromReferenceValueRetriever::class);

        $this->wiki_organizer = new CrossReferenceWikiOrganizer(
            $project_manager,
            $this->wiki_page_retriever,
        );

        $this->user = Mockery::mock(PFUser::class);

        $this->by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $this->by_nature_organizer->shouldReceive(['getCurrentUser' => $this->user]);
    }

    public function testItRemovesReferenceIfWikiPageIsNotAccessible()
    {
        $wiki_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('wiki_page')
            ->withValue('MyWikiPage')
            ->build();

        $this->wiki_page_retriever
            ->shouldReceive('getWikiPageUserCanView')
            ->with($this->project, $this->user, 'MyWikiPage')
            ->once()
            ->andReturnNull();

        $this->by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($wiki_ref)
            ->once();

        $this->wiki_organizer->organizeWikiReference($wiki_ref, $this->by_nature_organizer);
    }

    public function testItAddsWikiPageToUnlabelledSection()
    {
        $wiki_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('wiki_page')
            ->withValue('MyWikiPage')
            ->build();

        $this->wiki_page_retriever
            ->shouldReceive('getWikiPageUserCanView')
            ->with($this->project, $this->user, 'MyWikiPage')
            ->once()
            ->andReturn(Mockery::mock(WikiPage::class));

        $this->by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($wiki_ref, '')
            ->once();

        $this->wiki_organizer->organizeWikiReference($wiki_ref, $this->by_nature_organizer);
    }
}
