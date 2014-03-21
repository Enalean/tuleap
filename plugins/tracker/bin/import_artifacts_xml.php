<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'pre.php';

try {
    $user_manager = UserManager::instance();
    $user_manager->forceLogin($argv[1]);

    $tracker = TrackerFactory::instance()->getTrackerById($argv[2]);
    if ($tracker) {
        $artifact_factory      = Tracker_ArtifactFactory::instance();
        $formelement_factory   = Tracker_FormElementFactory::instance();
        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($formelement_factory);
        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();

        $artifact_creator = new Tracker_ArtifactCreator(
            $artifact_factory,
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                $formelement_factory,
                $changeset_dao,
                $artifact_factory
            )
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            $formelement_factory,
            $changeset_dao,
            $changeset_comment_dao,
            $artifact_factory,
            EventManager::instance(),
            ReferenceManager::instance()
        );

        $xml_import = new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            Tracker_FormElementFactory::instance(),
            $user_manager
        );

        $zip = new ZipArchive();
        if ($zip->open($argv[3]) !== true) {
            echo 'Impossible to open archive '.$argv[3].PHP_EOL;
            exit(1);
        }
        $archive = new Tracker_Artifact_XMLImport_XMLImportZipArchive(
            $tracker,
            $zip,
            Config::get('tmp_dir')
        );

        $xml_import->importFromArchive($tracker, $archive);
    }
} catch (XML_ParseException $exception) {
    echo $exception->getMessage().PHP_EOL;
    echo $exception->getIndentedXml().PHP_EOL;
    echo implode(PHP_EOL, $exception->getErrors()).PHP_EOL;
    exit(1);
}

