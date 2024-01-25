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

namespace Tuleap\Reference\ByNature\Forum;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\Forum\Forum;
use Tuleap\Forum\ForumRetriever;
use Tuleap\Forum\Message;
use Tuleap\Forum\MessageNotFoundException;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Forum\PermissionToAccessForumException;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferenceForumOrganizerTest extends TestCase
{
    private MessageRetriever&MockObject $message_retriever;
    private ForumRetriever&MockObject $forum_retriever;
    private Project $project;
    private CrossReferenceForumOrganizer $organizer;

    protected function setUp(): void
    {
        $this->project   = ProjectTestBuilder::aProject()->build();
        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($this->project);
        $this->message_retriever = $this->createMock(MessageRetriever::class);
        $this->forum_retriever   = $this->createMock(ForumRetriever::class);

        $this->organizer = new CrossReferenceForumOrganizer(
            $project_manager,
            $this->message_retriever,
            $this->forum_retriever,
        );
    }

    public function testItRemovesCrossReferenceIfMessageCannotBeFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->method('getMessage')
            ->with(123)
            ->willThrowException(new MessageNotFoundException());

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }

    public function testItRemovesCrossReferenceIfMessageCannotBeRead(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->method('getMessage')
            ->with(123)
            ->willThrowException(new PermissionToAccessForumException());

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }

    public function testItOrganizesCrossReferenceToUnlabelledSection(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->method('getMessage')
            ->with(123)
            ->willReturn(new Message(
                'Not able to access SVN repo',
                'whatever',
                'jdoe',
                1234567890,
                1,
                2,
                3,
                "Open Discussions",
            ));

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with(
                self::callback(function (CrossReferencePresenter $presenter): bool {
                    return $presenter->id === 1
                           && $presenter->title === 'Not able to access SVN repo';
                }),
                ''
            );

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }

    public function testItRemovesCrossReferenceIfForumCannotBeFound(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->forum_retriever
            ->method('getForumUserCanView')
            ->with(123, $this->project, $user)
            ->willReturn(null);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer
            ->expects(self::once())
            ->method('removeUnreadableCrossReference')
            ->with($ref);

        $this->organizer->organizeForumReference($ref, $by_nature_organizer);
    }

    public function testItOrganizesForumCrossReferenceToUnlabelledSection(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->forum_retriever
            ->method('getForumUserCanView')
            ->with(123, $this->project, $user)
            ->willReturn(new Forum('Open Discussions'));

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer
            ->expects(self::once())
            ->method('moveCrossReferenceToSection')
            ->with(
                self::callback(function (CrossReferencePresenter $presenter): bool {
                    return $presenter->id === 1
                           && $presenter->title === 'Open Discussions';
                }),
                ''
            );

        $this->organizer->organizeForumReference($ref, $by_nature_organizer);
    }
}
