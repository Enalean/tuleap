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

namespace Tuleap\Git\Hook\Asynchronous;

use Psr\Log\NullLogger;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushReceived;
use Tuleap\Git\Stub\Hook\Asynchronous\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class DefaultBranchPushParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const GIT_REPOSITORY_ID  = 419;
    private const FIRST_COMMIT_SHA1  = 'ee79c119';
    private const SECOND_COMMIT_SHA1 = '6337034b';
    private const PUSHING_USER_ID    = 148;
    private array $payload;
    private string $topic;
    private RetrieveUserByIdStub $user_retriever;
    private \PFUser $pushing_user;
    /**
     * @var \GitRepository & \PHPUnit\Framework\MockObject\Stub
     */
    private $git_repository;
    private RetrieveGitRepositoryStub $git_repository_retriever;

    protected function setUp(): void
    {
        $this->payload = [
            'git_repository_id' => self::GIT_REPOSITORY_ID,
            'commit_hashes'     => [self::FIRST_COMMIT_SHA1, self::SECOND_COMMIT_SHA1],
            'pushing_user_id'   => self::PUSHING_USER_ID,
        ];
        $this->topic   = AnalyzePushTask::TOPIC;

        $this->git_repository           = $this->createStub(\GitRepository::class);
        $this->pushing_user             = UserTestBuilder::aUser()->withId(self::PUSHING_USER_ID)->build();
        $this->user_retriever           = RetrieveUserByIdStub::withUser($this->pushing_user);
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withGitRepository($this->git_repository);
    }

    /**
     * @return Ok<DefaultBranchPushReceived> | Err<Fault>
     */
    private function parsePush(): Ok|Err
    {
        $worker_event = new WorkerEvent(new NullLogger(), [
            'event_name' => $this->topic,
            'payload'    => $this->payload,
        ]);
        $parser       = new DefaultBranchPushParser($this->user_retriever, $this->git_repository_retriever);
        return $parser->parse($worker_event);
    }

    public function testItBuildsADefaultBranchPushReceivedFromAWorkerEvent(): void
    {
        $result = $this->parsePush();

        self::assertTrue(Result::isOk($result));
        self::assertSame($this->git_repository, $result->value->getRepository());
        self::assertSame($this->pushing_user, $result->value->getPusher());
    }

    public function testItReturnsUnhandledTopicFaultWhenTopicDoesNotMatch(): void
    {
        $this->topic = 'bad topic';
        $result      = $this->parsePush();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnhandledTopicFault::class, $result->error);
    }

    public static function provideMalformedPayloads(): array
    {
        return [
            'missing git_repository_id'       => [[]],
            'git_repository_id is not an int' => [['git_repository_id' => 'abc']],
            'missing pushing_user_id'         => [['git_repository_id' => self::GIT_REPOSITORY_ID]],
            'pushing_user_id is not an int'   => [['git_repository_id' => self::GIT_REPOSITORY_ID, 'pushing_user_id' => 'abc']],
            'missing commit_hashes'           => [['git_repository_id' => self::GIT_REPOSITORY_ID, 'pushing_user_id' => self::PUSHING_USER_ID]],
            'commit_hashes is not an array'   => [['git_repository_id' => self::GIT_REPOSITORY_ID, 'pushing_user_id' => self::PUSHING_USER_ID, 'commit_sha1' => 'abc']],
        ];
    }

    /**
     * @dataProvider provideMalformedPayloads
     */
    public function testItReturnsFaultWhenPayloadIsMalformed(array $payload): void
    {
        $this->payload = $payload;
        self::assertTrue(Result::isErr($this->parsePush()));
    }

    public function testItReturnsFaultWhenPushingUserCantBeFound(): void
    {
        $this->user_retriever = RetrieveUserByIdStub::withNoUser();
        self::assertTrue(Result::isErr($this->parsePush()));
    }

    public function testItReturnsFaultWhenGitRepositoryDoesNotExistOrPushingUserCantSeeIt(): void
    {
        $this->git_repository_retriever = RetrieveGitRepositoryStub::withFault(
            Fault::fromMessage(
                'Could not retrieve git repository because
                - it has been deleted since the Worker Event was dispatched
                - or the repository access control has changed and now user does not have read permission
                - or its project permissions have changed'
            )
        );
        self::assertTrue(Result::isErr($this->parsePush()));
    }
}
