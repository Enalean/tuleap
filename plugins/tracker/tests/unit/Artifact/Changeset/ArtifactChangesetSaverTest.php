<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset;

use DateTimeImmutable;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetDao;
use Tracker_ArtifactDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactChangesetSaverTest extends TestCase
{
    private PFUser $user;
    private Artifact $artifact;
    private ArtifactChangesetSaver $saver;
    private ChangesetFromXmlDao&MockObject $changeset_from_xml_dao;
    private Tracker_ArtifactDao&MockObject $tracker_artifact_dao;
    private Tracker_Artifact_ChangesetDao&MockObject $changeset_dao;

    protected function setUp(): void
    {
        $this->changeset_dao          = $this->createMock(Tracker_Artifact_ChangesetDao::class);
        $this->tracker_artifact_dao   = $this->createMock(Tracker_ArtifactDao::class);
        $this->changeset_from_xml_dao = $this->createMock(ChangesetFromXmlDao::class);

        $this->saver = new ArtifactChangesetSaver(
            $this->changeset_dao,
            new DBTransactionExecutorPassthrough(),
            $this->tracker_artifact_dao,
            $this->changeset_from_xml_dao
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $this->user     = UserTestBuilder::anActiveUser()->withId(102)->build();
    }

    public function testItStoreChangeset(): void
    {
        $import_config = new TrackerNoXMLImportLoggedConfig();
        $time          = new DateTimeImmutable();

        $this->changeset_dao->expects($this->once())->method('create')->willReturn(1234);
        $this->tracker_artifact_dao->expects($this->once())->method('updateLastChangsetId');
        $this->changeset_from_xml_dao->expects($this->never())->method('saveChangesetIsCreatedFromXml');

        $this->saver->saveChangeset($this->artifact, $this->user, $time->getTimestamp(), $import_config);
    }

    public function testItStoreChangesetCreatedFromXML(): void
    {
        $time          = new DateTimeImmutable();
        $import_config = new TrackerXmlImportConfig($this->user, $time, MoveImportConfig::buildForRegularImport(), false);

        $this->changeset_dao->expects($this->once())->method('create')->willReturn(1234);
        $this->tracker_artifact_dao->expects($this->once())->method('updateLastChangsetId');
        $this->changeset_from_xml_dao->expects($this->once())->method('saveChangesetIsCreatedFromXml');

        $this->saver->saveChangeset($this->artifact, $this->user, $time->getTimestamp(), $import_config);
    }
}
