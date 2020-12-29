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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use Tuleap\Forum\Message;
use Tuleap\Forum\MessageNotFoundException;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Forum\PermissionToAccessForumException;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceForumOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MessageRetriever
     */
    private $message_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var CrossReferenceForumOrganizer
     */
    private $organizer;

    protected function setUp(): void
    {
        $this->message_retriever = Mockery::mock(MessageRetriever::class);
        $this->project           = Mockery::mock(\Project::class);
        $project_manager        = Mockery::mock(ProjectManager::class, ['getProject' => $this->project]);

        $this->organizer = new CrossReferenceForumOrganizer(
            $project_manager,
            $this->message_retriever,
        );
    }

    public function testItRemovesCrossReferenceIfMessageCannotBeFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->shouldReceive('getMessage')
            ->with(123)
            ->andThrow(MessageNotFoundException::class);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }

    public function testItRemovesCrossReferenceIfMessageCannotBeRead(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->shouldReceive('getMessage')
            ->with(123)
            ->andThrow(PermissionToAccessForumException::class);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }

    public function testItOrganizesCrossReferenceToUnlabelledSection(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withValue("123")
            ->build();

        $this->message_retriever
            ->shouldReceive('getMessage')
            ->with(123)
            ->andReturn(
                new Message(
                    'Not able to access SVN repo',
                    'whatever',
                    'jdoe',
                    1234567890,
                    1,
                    2,
                    3,
                    "Open Discussions",
                )
            );

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                $this->project,
                Mockery::on(
                    function (CrossReferencePresenter $presenter): bool {
                        return $presenter->id === 1
                            && $presenter->title === 'Not able to access SVN repo';
                    }
                ),
                ''
            )
            ->once();

        $this->organizer->organizeMessageReference($ref, $by_nature_organizer);
    }
}
