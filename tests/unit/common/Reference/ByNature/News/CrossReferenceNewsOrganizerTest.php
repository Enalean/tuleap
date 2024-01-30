<?php
/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Reference\ByNature\News;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\News\Exceptions\NewsNotFoundException;
use Tuleap\News\Exceptions\RestrictedNewsAccessException;
use Tuleap\News\NewsItem;
use Tuleap\News\NewsRetriever;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

final class CrossReferenceNewsOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CrossReferenceNewsOrganizer $organizer;
    private NewsRetriever&MockObject $news_retriever;

    protected function setUp(): void
    {
        $this->news_retriever = $this->createMock(NewsRetriever::class);
        $this->organizer      = new CrossReferenceNewsOrganizer(
            $this->news_retriever
        );
    }

    public function testItRemovesTheReferenceWhenForumIsNotFound(): void
    {
        $a_ref               = $this->buildReference();
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);

        $this->news_retriever->method('getNewsUserCanView')
            ->with(28)
            ->willThrowException(new NewsNotFoundException());

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($a_ref);

        $this->organizer->organizeNewsReference(
            $a_ref,
            $by_nature_organizer
        );
    }

    public function testItRemovesTheReferenceWhenUserHasNotThePermissionToAccessTheForum(): void
    {
        $a_ref               = $this->buildReference();
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);

        $this->news_retriever->method('getNewsUserCanView')
            ->with(28)
            ->willThrowException(new RestrictedNewsAccessException());

        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($a_ref);

        $this->organizer->organizeNewsReference(
            $a_ref,
            $by_nature_organizer
        );
    }

    public function testItMovesTheCrossReferenceToUnlabelledSection(): void
    {
        $a_ref               = $this->buildReference();
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);

        $this->news_retriever->method('getNewsUserCanView')
            ->with(28)
            ->willReturn(new NewsItem([
                'id'          => 10,
                'is_approved' => 1,
                'summary'     => 'Secret news',
            ]));

        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with(
                self::callback(function (CrossReferencePresenter $presenter): bool {
                    return $presenter->id === 5
                           && $presenter->title === 'Secret news';
                }),
                ''
            );

        $this->organizer->organizeNewsReference(
            $a_ref,
            $by_nature_organizer
        );
    }

    private function buildReference(): CrossReferencePresenter
    {
        return CrossReferencePresenterBuilder::get(5)
            ->withProjectId(104)
            ->withValue('28')
            ->withType(\ReferenceManager::REFERENCE_NATURE_NEWS)
            ->build();
    }
}
