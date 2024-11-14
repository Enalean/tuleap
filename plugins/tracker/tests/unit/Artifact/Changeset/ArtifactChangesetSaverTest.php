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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_ChangesetDao;
use Tracker_ArtifactDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;

final class ArtifactChangesetSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var ArtifactChangesetSaver
     */
    private $saver;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ChangesetFromXmlDao
     */
    private $changeset_from_xml_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactDao
     */
    private $tracker_artifact_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_ChangesetDao
     */
    private $changeset_dao;

    protected function setUp(): void
    {
        $transaction_executor = new DBTransactionExecutorPassthrough();

        $this->changeset_dao          = \Mockery::mock(Tracker_Artifact_ChangesetDao::class);
        $this->tracker_artifact_dao   = \Mockery::mock(Tracker_ArtifactDao::class);
        $this->changeset_from_xml_dao = \Mockery::mock(ChangesetFromXmlDao::class);

        $this->saver = new ArtifactChangesetSaver(
            $this->changeset_dao,
            $transaction_executor,
            $this->tracker_artifact_dao,
            $this->changeset_from_xml_dao
        );

        $this->artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->user = \Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(102);
        $this->user->shouldReceive('isAnonymous')->andReturnFalse();
    }

    public function testItStoreChangeset(): void
    {
        $import_config = new TrackerNoXMLImportLoggedConfig();
        $time          = new \DateTimeImmutable();

        $this->changeset_dao->shouldReceive('create')->once()->andReturn(1234);
        $this->tracker_artifact_dao->shouldReceive('updateLastChangsetId')->once();
        $this->changeset_from_xml_dao->shouldReceive('saveChangesetIsCreatedFromXml')->never();

        $this->saver->saveChangeset($this->artifact, $this->user, $time->getTimestamp(), $import_config);
    }

    public function testItStoreChangesetCreatedFromXML(): void
    {
        $time          = new \DateTimeImmutable();
        $import_config = new TrackerXmlImportConfig($this->user, $time, MoveImportConfig::buildForRegularImport(), false);

        $this->changeset_dao->shouldReceive('create')->once()->andReturn(1234);
        $this->tracker_artifact_dao->shouldReceive('updateLastChangsetId')->once();
        $this->changeset_from_xml_dao->shouldReceive('saveChangesetIsCreatedFromXml')->once();

        $this->saver->saveChangeset($this->artifact, $this->user, $time->getTimestamp(), $import_config);
    }
}
