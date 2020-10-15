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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_Changeset_IncomingMailGoldenRetrieverTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_Artifact_Changeset_IncomingMailDao */
    private $dao;

    /** @var Tracker_Artifact_Changeset_IncomingMailGoldenRetriever */
    private $retriever;

    /** @var Artifact */
    private $artifact_by_mail;

    /** @var Artifact */
    private $artifact_by_web;

    /** @var Tracker_Artifact_Changeset */
    private $changeset_by_mail;

    /** @var Tracker_Artifact_Changeset */
    private $changeset_by_web;

    private $raw_mail_creation = 'raw mail content for creation';
    private $raw_mail_update   = 'raw mail content for update';
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $other_changeset_by_mail;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(\Tracker_Artifact_Changeset_IncomingMailDao::class);

        $this->changeset_by_mail       = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(1)->getMock();
        $this->other_changeset_by_mail = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(2)->getMock();

        $this->artifact_by_mail = $this->buildArtifactWithChangesets(123, [$this->changeset_by_mail, $this->other_changeset_by_mail]);
        $this->changeset_by_mail->shouldReceive('getArtifact')->andReturns($this->artifact_by_mail);
        $this->other_changeset_by_mail->shouldReceive('getArtifact')->andReturns($this->artifact_by_mail);

        $this->changeset_by_web = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(3)->getMock();
        $changeset_by_web_2     = \Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(4)->getMock();
        $this->artifact_by_web  = $this->buildArtifactWithChangesets(456, [$this->changeset_by_web, $changeset_by_web_2]);
        $this->changeset_by_web->shouldReceive('getArtifact')->andReturns($this->artifact_by_web);

        $this->retriever = new Tracker_Artifact_Changeset_IncomingMailGoldenRetriever($this->dao);
    }

    private function buildArtifactWithChangesets(int $id, array $changesets): Artifact
    {
        $artifact = new Artifact($id, 123, null, 10, null);
        $artifact->setChangesets($changesets);

        return $artifact;
    }

    public function testItRetrievesRawMailThatCreatedArtifact(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(123)->andReturns(\TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => $this->raw_mail_creation], ['changeset_id' => 2, 'raw_mail' => $this->raw_mail_update]));
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);

        $this->assertEquals($this->raw_mail_creation, $raw_mail);
    }

    public function testItRetrievesNoRawMailIfArtifactWasNotCreatedByMail(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(456)->andReturns(\TestHelper::emptyDar());
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_web);

        $this->assertNull($raw_mail);
    }

    public function testItRetrievesRawMailThatCreatedChangeset(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(123)->andReturns(\TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => $this->raw_mail_creation], ['changeset_id' => 2, 'raw_mail' => $this->raw_mail_update]));
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);

        $this->assertEquals($this->raw_mail_creation, $raw_mail);
    }

    public function testItRetrievesRawMailThatCreatedOtherChangeset(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(123)->andReturns(\TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => $this->raw_mail_creation], ['changeset_id' => 2, 'raw_mail' => $this->raw_mail_update]));
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);

        $this->assertEquals($this->raw_mail_update, $raw_mail);
    }

    public function testItRetrievesNoRawMailIfChangesetWasNotCreatedByMail(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(456)->andReturns(\TestHelper::emptyDar());
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_web);

        $this->assertNull($raw_mail);
    }

    public function testItCachesResultsToSaveTheRainForestAndKittens(): void
    {
        $this->dao->shouldReceive('searchByArtifactId')->with(123)->once()->andReturns(\TestHelper::arrayToDar(['changeset_id' => 1, 'raw_mail' => $this->raw_mail_creation], ['changeset_id' => 2, 'raw_mail' => $this->raw_mail_update]));

        $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);
    }
}
