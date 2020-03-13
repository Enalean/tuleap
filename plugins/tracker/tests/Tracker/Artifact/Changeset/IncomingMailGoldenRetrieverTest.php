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

require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_Artifact_Changeset_IncomingMailGoldenRetrieverTest extends TuleapTestCase
{

    /** @var Tracker_Artifact_Changeset_IncomingMailDao */
    private $dao;

    /** @var Tracker_Artifact_Changeset_IncomingMailGoldenRetriever */
    private $retriever;

    /** @var Tracker_Artifact */
    private $artifact_by_mail;

    /** @var Tracker_Artifact */
    private $artifact_by_web;

    /** @var Tracker_Artifact_Changeset */
    private $changeset_by_mail;

    /** @var Tracker_Artifact_Changeset */
    private $changeset_by_web;

    private $raw_mail_creation = 'raw mail content for creation';
    private $raw_mail_update   = 'raw mail content for update';

    public function setUp()
    {
        parent::setUp();

        $this->dao = mock('Tracker_Artifact_Changeset_IncomingMailDao');
        stub($this->dao)->searchByArtifactId(123)->returnsDar(
            array('changeset_id' => 1, 'raw_mail' => $this->raw_mail_creation),
            array('changeset_id' => 2, 'raw_mail' => $this->raw_mail_update)
        );
        stub($this->dao)->searchByArtifactId(456)->returnsEmptyDar();

        $this->changeset_by_mail       = stub('Tracker_Artifact_Changeset')->getId()->returns(1);
        $this->other_changeset_by_mail = stub('Tracker_Artifact_Changeset')->getId()->returns(2);

        $this->artifact_by_mail = anArtifact()
            ->withId(123)
            ->withChangesets(array(
                $this->changeset_by_mail,
                $this->other_changeset_by_mail
            ))
            ->build();
        stub($this->changeset_by_mail)->getArtifact()->returns($this->artifact_by_mail);
        stub($this->other_changeset_by_mail)->getArtifact()->returns($this->artifact_by_mail);

        $this->changeset_by_web = stub('Tracker_Artifact_Changeset')->getId()->returns(3);
        $changeset_by_web_2     = stub('Tracker_Artifact_Changeset')->getId()->returns(4);
        $this->artifact_by_web  = anArtifact()
            ->withId(456)
            ->withChangesets(array(
                $this->changeset_by_web,
                $changeset_by_web_2
            ))
            ->build();
        stub($this->changeset_by_web)->getArtifact()->returns($this->artifact_by_web);

        $this->retriever = new Tracker_Artifact_Changeset_IncomingMailGoldenRetriever($this->dao);
    }

    public function itRetrievesRawMailThatCreatedArtifact()
    {
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);

        $this->assertEqual($raw_mail, $this->raw_mail_creation);
    }

    public function itRetrievesNoRawMailIfArtifactWasNotCreatedByMail()
    {
        $raw_mail = $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_web);

        $this->assertNull($raw_mail);
    }

    public function itRetrievesRawMailThatCreatedChangeset()
    {
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);

        $this->assertEqual($raw_mail, $this->raw_mail_creation);
    }

    public function itRetrievesRawMailThatCreatedOtherChangeset()
    {
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);

        $this->assertEqual($raw_mail, $this->raw_mail_update);
    }

    public function itRetrievesNoRawMailIfChangesetWasNotCreatedByMail()
    {
        $raw_mail = $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_web);

        $this->assertNull($raw_mail);
    }

    public function itCachesResultsToSaveTheRainForestAndKittens()
    {
        expect($this->dao)->searchByArtifactId()->once();

        $this->retriever->getRawMailThatCreatedArtifact($this->artifact_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->changeset_by_mail);
        $this->retriever->getRawMailThatCreatedChangeset($this->other_changeset_by_mail);
    }
}
