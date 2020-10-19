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

use PFUser;
use Tracker_Artifact_ChangesetDao;
use Tracker_ArtifactDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;

class ArtifactChangesetSaver
{
    /**
     * @var Tracker_Artifact_ChangesetDao
     */
    private $changeset_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var Tracker_ArtifactDao
     */
    private $tracker_artifact_dao;
    /**
     * @var ChangesetFromXmlDao
     */
    private $changeset_from_xml_dao;

    public function __construct(
        Tracker_Artifact_ChangesetDao $changeset_dao,
        DBTransactionExecutor $transaction_executor,
        Tracker_ArtifactDao $tracker_artifact_dao,
        ChangesetFromXmlDao $changeset_from_xml_dao
    ) {
        $this->changeset_dao          = $changeset_dao;
        $this->transaction_executor   = $transaction_executor;
        $this->tracker_artifact_dao   = $tracker_artifact_dao;
        $this->changeset_from_xml_dao = $changeset_from_xml_dao;
    }

    public static function build(): self
    {
        return new self(
            new Tracker_Artifact_ChangesetDao(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            new Tracker_ArtifactDao(),
            new ChangesetFromXmlDao()
        );
    }

    /**
     * @throws \Tracker_Artifact_Exception_CannotCreateNewChangeset
     */
    public function saveChangeset(
        Artifact $artifact,
        PFUser $submitter,
        int $submitted_on,
        TrackerImportConfig $import_config
    ): int {
        return $this->transaction_executor->execute(
            function () use ($artifact, $submitter, $submitted_on, $import_config) {
                $email = null;
                if ($submitter->isAnonymous()) {
                    $email = $submitter->getEmail();
                }

                $changeset_id = $this->changeset_dao->create(
                    $artifact->getId(),
                    $submitter->getId(),
                    $email,
                    $submitted_on
                );

                if (! $changeset_id) {
                    throw new \Tracker_Artifact_Exception_CannotCreateNewChangeset();
                }

                $this->tracker_artifact_dao->updateLastChangsetId((int) $changeset_id, (int) $artifact->getId());


                if ($import_config->isFromXml()) {
                    assert($import_config instanceof TrackerXmlImportConfig);

                    $this->changeset_from_xml_dao->saveChangesetIsCreatedFromXml(
                        $import_config->getImportTimestamp(),
                        $import_config->getUserId(),
                        (int) $changeset_id
                    );
                }

                return (int) $changeset_id;
            }
        );
    }
}
