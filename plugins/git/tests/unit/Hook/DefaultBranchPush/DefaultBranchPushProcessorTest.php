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

use Tuleap\Git\Repository\Settings\ArtifactClosure\ArtifactClosureNotAllowedFault;
use Tuleap\Git\Tests\Stub\RetrieveAuthorStub;
use Tuleap\Git\Tests\Stub\RetrieveCommitMessageStub;
use Tuleap\Git\Tests\Stub\VerifyArtifactClosureIsAllowedStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\UserName;

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
     * @return Ok<DefaultBranchPushProcessed> | Err<Fault>
     */
    private function process(): Ok|Err
    {
        $git_repository = $this->createStub(\GitRepository::class);
        $git_repository->method('getId')->willReturn(98);
        $git_repository->method('getFullName')->willReturn(self::REPOSITORY_PATH);
        $git_repository->method('getProject')->willReturn($this->project);

        $processor = new DefaultBranchPushProcessor($this->closure_verifier, $this->message_retriever, RetrieveAuthorStub::buildWithUser(UserName::fromUser($this->pusher)));
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
        $result = $this->process();

        self::assertTrue(Result::isOk($result));
        $processed = $result->value;
        self::assertSame($this->project, $processed->event->project);
        self::assertEmpty($processed->faults);
        $user = UserName::fromUser($this->pusher);

        self::assertCount(2, $processed->event->text_with_potential_references);
        [$first_text, $second_text] = $processed->event->text_with_potential_references;

        self::assertSame(self::FIRST_COMMIT_MESSAGE, $first_text->text);
        self::assertSame(
            sprintf('%s #%s/%s', \Git::REFERENCE_KEYWORD, self::REPOSITORY_PATH, self::FIRST_COMMIT_SHA1),
            $first_text->back_reference->getStringReference()
        );
        self::assertEquals(
            $user->getName(),
            $first_text->user_name->getName()
        );

        self::assertSame(self::SECOND_COMMIT_MESSAGE, $second_text->text);
        self::assertSame(
            sprintf('%s #%s/%s', \Git::REFERENCE_KEYWORD, self::REPOSITORY_PATH, self::SECOND_COMMIT_SHA1),
            $second_text->back_reference->getStringReference()
        );
        self::assertEquals(
            $user->getName(),
            $second_text->user_name->getName()
        );
    }

    public function testItReturnsASpecializedFaultWhenArtifactClosureIsDisabledInTheRepository(): void
    {
        $this->closure_verifier = VerifyArtifactClosureIsAllowedStub::withNeverAllowed();

        $result = $this->process();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ArtifactClosureNotAllowedFault::class, $result->error);
    }

    public function testItReturnsProcessedContainingFaultsWhenItCannotReadCommitMessages(): void
    {
        // Do not stop execution because we cannot read one commit message. We process all commits.
        $this->message_retriever = RetrieveCommitMessageStub::withError();

        $result = $this->process();

        self::assertTrue(Result::isOk($result));
        self::assertCount(2, $result->value->faults);
        self::assertEmpty($result->value->event->text_with_potential_references);
    }
}
