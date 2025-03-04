<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_IncomingMailDao;
use Tracker_Artifact_Changeset_IncomingMailGoldenRetriever;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_Changeset_IncomingMailGoldenRetrieverTest extends TestCase
{
    private const RAW_MAIL_UPDATE   = 'raw mail content for update';
    private const RAW_MAIL_CREATION = 'raw mail content for creation';

    private Tracker_Artifact_Changeset_IncomingMailDao&MockObject $dao;
    private Tracker_Artifact_Changeset_IncomingMailGoldenRetriever $retriever;
    private Artifact $artifact_by_mail;
    private Artifact $artifact_by_web;
    private Tracker_Artifact_Changeset $changeset_by_mail;
    private Tracker_Artifact_Changeset $changeset_by_web;
    private Tracker_Artifact_Changeset $other_changeset_by_mail;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(Tracker_Artifact_Changeset_IncomingMailDao::class);

        $this->changeset_by_mail                 = ChangesetTestBuilder::aChangeset(1)->build();
        $this->other_changeset_by_mail           = ChangesetTestBuilder::aChangeset(2)->build();
        $this->artifact_by_mail                  = ArtifactTestBuilder::anArtifact(123)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(123)->build())
            ->withChangesets($this->other_changeset_by_mail, $this->changeset_by_mail)
            ->build();
        $this->changeset_by_mail->artifact       = $this->artifact_by_mail;
        $this->other_changeset_by_mail->artifact = $this->artifact_by_mail;

        $this->changeset_by_web           = ChangesetTestBuilder::aChangeset(3)->build();
        $changeset_by_web_2               = ChangesetTestBuilder::aChangeset(4)->build();
        $this->artifact_by_web            = ArtifactTestBuilder::anArtifact(456)
            ->inTracker(TrackerTestBuilder::aTracker()->withId(123)->build())
            ->withChangesets($changeset_by_web_2, $this->changeset_by_web)
            ->build();
        $this->changeset_by_web->artifact = $this->artifact_by_web;

        $this->retriever = new Tracker_Artifact_Changeset_IncomingMailGoldenRetriever($this->dao);
    }

    public function testItRetrievesRawMailThatCreatedArtifact(): void
    {
        $this->dao->method('searchByArtifactId')->with(123)->willReturn(TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => self::RAW_MAIL_CREATION], ['changeset_id' => 2, 'raw_mail' => self::RAW_MAIL_UPDATE]));
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);

        self::assertEquals(self::RAW_MAIL_CREATION, $raw_mail);
    }

    public function testItRetrievesNoRawMailIfArtifactWasNotCreatedByMail(): void
    {
        $this->dao->method('searchByArtifactId')->with(456)->willReturn(TestHelper::emptyDar());
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_web);

        self::assertNull($raw_mail);
    }

    public function testItRetrievesRawMailThatCreatedChangeset(): void
    {
        $this->dao->method('searchByArtifactId')->with(123)->willReturn(TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => self::RAW_MAIL_CREATION], ['changeset_id' => 2, 'raw_mail' => self::RAW_MAIL_UPDATE]));
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);

        self::assertEquals(self::RAW_MAIL_CREATION, $raw_mail);
    }

    public function testItRetrievesRawMailThatCreatedOtherChangeset(): void
    {
        $this->dao->method('searchByArtifactId')->with(123)->willReturn(TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => self::RAW_MAIL_CREATION], ['changeset_id' => 2, 'raw_mail' => self::RAW_MAIL_UPDATE]));
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);

        self::assertEquals(self::RAW_MAIL_UPDATE, $raw_mail);
    }

    public function testItRetrievesNoRawMailIfChangesetWasNotCreatedByMail(): void
    {
        $this->dao->method('searchByArtifactId')->with(456)->willReturn(TestHelper::emptyDar());
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_web);

        self::assertNull($raw_mail);
    }

    public function testItCachesResultsToSaveTheRainForestAndKittens(): void
    {
        $this->dao->expects(self::once())->method('searchByArtifactId')->with(123)->willReturn(TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => self::RAW_MAIL_CREATION], ['changeset_id' => 2, 'raw_mail' => self::RAW_MAIL_UPDATE]));

        $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);
    }
}
