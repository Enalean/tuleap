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

use Psr\Log\Test\TestLogger;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Git\Hook\CommitHash;
use Tuleap\Git\Stub\EventDispatcherStub;
use Tuleap\Git\Stub\RetrieveCommitMessageStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class CommitAnalysisProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID     = 163;
    private const COMMIT_MESSAGE = 'richly philomelanist';
    private const COMMIT_SHA1    = '6c31bec0c';
    private TestLogger $logger;
    private RetrieveCommitMessageStub $message_retriever;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->logger            = new TestLogger();
        $this->message_retriever = RetrieveCommitMessageStub::withMessage(self::COMMIT_MESSAGE);
        $this->event_dispatcher  = EventDispatcherStub::withCallback(static fn($event) => $event);
    }

    private function process(): void
    {
        $processor = new CommitAnalysisProcessor(
            $this->logger,
            $this->message_retriever,
            $this->event_dispatcher,
        );
        $processor->process(
            CommitAnalysisOrder::fromComponents(
                CommitHash::fromString(self::COMMIT_SHA1),
                UserTestBuilder::buildWithDefaults(),
                ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()
            )
        );
    }

    public function testItDispatchesAnEventToSearchReferencesOnTheCommitMessageFromTheGivenHash(): void
    {
        $event                  = null;
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (PotentialReferencesReceived $inner) use (&$event) {
                $event = $inner;
                return $inner;
            }
        );
        $this->process();

        self::assertNotNull($event);
    }

    public function testItLogsErrorWhenItCannotReadCommitMessage(): void
    {
        $this->message_retriever = RetrieveCommitMessageStub::withError();
        $this->process();

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
