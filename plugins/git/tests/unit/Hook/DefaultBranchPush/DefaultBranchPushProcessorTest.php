<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\DefaultBranchPush;

use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ArtifactClosureNotAllowedFault;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Git\Stub\VerifyArtifactClosureIsAllowedStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DefaultBranchPushProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_COMMIT_MESSAGE  = 'closes story #822';
    private const SECOND_COMMIT_MESSAGE = 'fixes bug #684';
    private const FIRST_COMMIT_SHA1     = '6c31bec0c';
    private const SECOND_COMMIT_SHA1    = 'abf44468';
    private const REPOSITORY_PATH       = 'cymogene/homiletics';
    private RetrieveCommitMessageStub $message_retriever;
    private \Project $project;
    private \PFUser $pusher;
    private VerifyArtifactClosureIsAllowedStub $closure_verifier;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(163)->build();
        $this->pusher  = UserTestBuilder::buildWithDefaults();

        $this->closure_verifier  = VerifyArtifactClosureIsAllowedStub::withAlwaysAllowed();
        $this->message_retriever = RetrieveCommitMessageStub::withSuccessiveMessages(
            self::FIRST_COMMIT_MESSAGE,
            self::SECOND_COMMIT_MESSAGE
        );
    }

    /**
     * @return list<Ok<PotentialReferencesReceived> | Err<Fault>>
     */
    private function process(): array
    {
        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getId')->willReturn(98);
        $git_repository->method('getFullName')->willReturn(self::REPOSITORY_PATH);
        $git_repository->method('getProject')->willReturn($this->project);

        $processor = new DefaultBranchPushProcessor($this->closure_verifier, $this->message_retriever);
        return $processor->process(
            new DefaultBranchPushReceived(
                $git_repository,
                $this->pusher,
                [CommitHash::fromString(self::FIRST_COMMIT_SHA1), CommitHash::fromString(self::SECOND_COMMIT_SHA1)],
            )
        );
    }

    public function testItReturnsAnEventToSearchForReferencesOnTheCommitMessageFromEachCommitOfThePush(): void
    {
        $results = $this->process();

        self::assertCount(2, $results);
        self::assertTrue(Result::isOk($results[0]));
        $first_event = $results[0]->value;

        self::assertSame(self::FIRST_COMMIT_MESSAGE, $first_event->text_with_potential_references);
        self::assertSame($this->project, $first_event->project);
        self::assertSame($this->pusher, $first_event->user);
        self::assertSame(
            sprintf('%s #%s/%s', \Git::REFERENCE_KEYWORD, self::REPOSITORY_PATH, self::FIRST_COMMIT_SHA1),
            $first_event->back_reference->getStringReference()
        );

        self::assertTrue(Result::isOk($results[1]));
        $second_event = $results[1]->value;

        self::assertSame(self::SECOND_COMMIT_MESSAGE, $second_event->text_with_potential_references);
        self::assertSame($this->project, $second_event->project);
        self::assertSame($this->pusher, $second_event->user);
        self::assertSame(
            sprintf('%s #%s/%s', \Git::REFERENCE_KEYWORD, self::REPOSITORY_PATH, self::SECOND_COMMIT_SHA1),
            $second_event->back_reference->getStringReference()
        );
    }

    public function testItReturnsASpecializedFaultWhenArtifactClosureIsDisabledInTheRepository(): void
    {
        $this->closure_verifier = VerifyArtifactClosureIsAllowedStub::withNeverAllowed();

        $results = $this->process();

        self::assertCount(1, $results);
        self::assertTrue(Result::isErr($results[0]));
        self::assertInstanceOf(ArtifactClosureNotAllowedFault::class, $results[0]->error);
    }

    public function testItReturnsFaultsWhenItCannotReadCommitMessages(): void
    {
        $this->message_retriever = RetrieveCommitMessageStub::withError();

        $results = $this->process();

        self::assertCount(2, $results);
        self::assertTrue(Result::isErr($results[0]));
        self::assertTrue(Result::isErr($results[1]));
    }
}
