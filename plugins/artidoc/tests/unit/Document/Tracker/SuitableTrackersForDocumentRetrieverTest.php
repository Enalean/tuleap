<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Stubs\Document\Tracker\CheckTrackerIsSuitableForDocumentStub;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackersByProjectIdUserCanViewStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SuitableTrackersForDocumentRetrieverTest extends TestCase
{
    private Tracker $stories;
    private Tracker $bugs;
    private Tracker $tasks;
    private Tracker $reqs;
    private ArtidocWithContext $document_information;
    private \PFUser $user;


    #[\Override]
    protected function setUp(): void
    {
        $this->stories = TrackerTestBuilder::aTracker()->withId(101)->build();
        $this->bugs    = TrackerTestBuilder::aTracker()->withId(102)->build();
        $this->tasks   = TrackerTestBuilder::aTracker()->withId(103)->build();
        $this->reqs    = TrackerTestBuilder::aTracker()->withId(104)->build();

        $service_docman = $this->createMock(ServiceDocman::class);
        $service_docman->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->document_information = (new ArtidocWithContext(
            new ArtidocDocument(['item_id' => 1]),
        ))->withContext(ServiceDocman::class, $service_docman);

        $this->user = UserTestBuilder::aUser()->build();
    }

    public function testWithoutTrackers(): void
    {
        $retriever = new SuitableTrackersForDocumentRetriever(
            CheckTrackerIsSuitableForDocumentStub::withoutSuitableTracker(),
            RetrieveTrackersByProjectIdUserCanViewStub::withTrackers($this->stories, $this->bugs, $this->tasks, $this->reqs),
        );

        self::assertEmpty($retriever->getTrackers($this->document_information, $this->user));
    }

    public function testWithTrackers(): void
    {
        $retriever = new SuitableTrackersForDocumentRetriever(
            CheckTrackerIsSuitableForDocumentStub::withSuitableTrackers($this->bugs, $this->tasks),
            RetrieveTrackersByProjectIdUserCanViewStub::withTrackers($this->stories, $this->bugs, $this->tasks, $this->reqs),
        );

        self::assertEquals(
            [$this->bugs, $this->tasks],
            $retriever->getTrackers($this->document_information, $this->user),
        );
    }
}
