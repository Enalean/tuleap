<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Tracker_Migration_MigrationManager {

    const INDENT_XSL_RESOURCE = '/xml/indent.xsl';

    const LOG_FILE = 'tv3_to_tv5.log';

    /** @var  Tracker_SystemEventManager */
    private $system_event_manager;

    /** @var  TrackerFactory */
    private $tracker_factory;

    /** @var  UserManager */
    private $user_manager;

    /** @var  ProjectManager */
    private $project_manager;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var  Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var  BackendLogger */
    private $logger;

    public function __construct(Tracker_SystemEventManager $system_event_manager, TrackerFactory $tracker_factory, Tracker_ArtifactFactory $artifact_factory, Tracker_FormElementFactory $form_element_factory, UserManager $user_manager, ProjectManager $project_manager) {
        $this->system_event_manager = $system_event_manager;
        $this->tracker_factory      = $tracker_factory;
        $this->user_manager         = $user_manager;
        $this->project_manager      = $project_manager;
        $this->form_element_factory = $form_element_factory;
        $this->artifact_factory     = $artifact_factory;

        $this->logger = new Tracker_Migration_MigrationLogger(new BackendLogger($this->getLogFilePath()));
    }

    /**
     * Launch the migration of a TV3 to a TV5
     *
     * @param Project $project
     * @param $tracker_id
     * @param $name
     * @param $description
     * @param $short_name
     *
     * @return bool true if everything seems right
     */
    public function askForMigration(Project $project, $tracker_id, $name, $description, $short_name) {
        if (! $this->tracker_factory->validMandatoryInfoOnCreate($name, $description, $short_name, $project->getGroupId())) {
            return false;
        }

        $this->system_event_manager->queueTV3Migration($this->user_manager->getCurrentUser(), $project, $tracker_id, $name, $description, $short_name);
        return true;
    }

    public function migrate($username, $project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name) {
        $this->logger->info('-- Beginning of migration of tracker v3 '.$tv3_id.' to '.$tracker_name.' --');

        $tracker_id   = $this->createTrackerStructure($project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name);
        $archive_path = $this->exportTV3Data($tv3_id);
        $this->importArtifactsData($username, $tracker_id, $archive_path);

        $this->logger->info('-- End of migration of tracker v3 '.$tv3_id.' to '.$tracker_name.' --');
        $this->logger->sendMail($this->user_manager->getUserByUserName($username), $this->project_manager->getProject($project_id), $tv3_id, $tracker_name);
    }

    private function getLogFilePath() {
        return Config::get('codendi_log').'/'.self::LOG_FILE;
    }

    private function importArtifactsData($username, $tracker_id, $archive_path) {
        $this->user_manager->forceLogin($username);

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker) {
            $xml_import = $this->getXMLImporter();

            $zip = new ZipArchive();

            if ($zip->open($archive_path) !== true) {
                throw new Tracker_Exception_Migration_OpenArchiveException($archive_path);
            }

            $archive = new Tracker_Artifact_XMLImport_XMLImportZipArchive(
                $tracker,
                $zip,
                Config::get('tmp_dir')
            );

            $xml_import->importFromArchive($tracker, $archive);
        }
    }

    private function getXMLImporter() {
        $fields_validator = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($this->form_element_factory);
        $changeset_dao    = new Tracker_Artifact_ChangesetDao();

        return new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $this->getArtifactCreator($fields_validator, $changeset_dao),
            $this->getChangesetCreator($fields_validator, $changeset_dao),
            $this->form_element_factory,
            new Tracker_Artifact_XMLImport_XMLImportHelper($this->user_manager),
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $this->logger
        );
    }

    private function getArtifactCreator(Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator, Tracker_Artifact_ChangesetDao $changeset_dao) {
        return new Tracker_ArtifactCreator(
            $this->artifact_factory,
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                $this->form_element_factory,
                $changeset_dao,
                $this->artifact_factory
            )
        );
    }

    private function getChangesetCreator(Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator, Tracker_Artifact_ChangesetDao $changeset_dao) {
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();

        return new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            $this->form_element_factory,
            $changeset_dao,
            $changeset_comment_dao,
            $this->artifact_factory,
            EventManager::instance(),
            ReferenceManager::instance()
        );
    }

    private function exportTV3Data($tv3_id) {
        $archive_path    = $this->generateTemporaryPath();
        $indent_xsl_path = $this->getIndentXSLResourcePath();
        $xml             = new DOMDocument("1.0", "UTF8");
        $archive         = new ZipArchive();
        if ($archive->open($archive_path, ZipArchive::CREATE) !== true) {
            throw new Tracker_Exception_Migration_CreateArchiveException($archive_path);
        }

        $dao                 = new ArtifactXMLExporterDao();
        $node_helper         = new ArtifactXMLNodeHelper($xml);
        $attachment_exporter = new ArtifactAttachmentXMLExporter($node_helper, $dao, $archive, false);

        $exporter = new ArtifactXMLExporter($dao, $attachment_exporter, $node_helper, $this->logger);
        $exporter->exportTrackerData($tv3_id);

        $xsl = new DOMDocument();
        $xsl->load($indent_xsl_path);

        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xsl);

        $archive->addFromString('artifacts.xml', $proc->transformToXML($xml));

        $archive->close();

        return $archive_path;
    }

    private function getIndentXSLResourcePath() {
        return Config::get('codendi_utils_prefix') . self::INDENT_XSL_RESOURCE;
    }

    private function generateTemporaryPath() {
        // Generate a temporary File
        $file_path = tempnam(Config::get('tmp_dir'), '');
        // Erase it but keep the path
        unlink($file_path);

        return $file_path;
    }

    private function createTrackerStructure($project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name) {
        $project = $this->project_manager->getProject($project_id);
        $new_tracker = $this->tracker_factory->createFromTV3($tv3_id, $project, $tracker_name, $tracker_description, $tracker_short_name);
        if (! $new_tracker) {
            throw new Tracker_Exception_Migration_StructureCreationException($tracker_name, $tv3_id);
        }

        return $new_tracker->getId();
    }
}