<?php
/**
 * Copyright Enalean (c) 2014 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\TrackerIsInvalidException;

class Tracker_Migration_MigrationManager
{

    public const INDENT_XSL_RESOURCE = '/xml/indent.xsl';

    public const LOG_FILE = 'tv3_tv5_migration_syslog';

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

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Tracker_Migration_MailLogger */
    private $mail_logger;
    /**
     * @var TrackerCreationDataChecker
     */
    private $creation_data_checker;

    public function __construct(
        Tracker_SystemEventManager $system_event_manager,
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory,
        UserManager $user_manager,
        ProjectManager $project_manager,
        TrackerCreationDataChecker $creation_data_checker
    ) {
        $this->system_event_manager = $system_event_manager;
        $this->tracker_factory      = $tracker_factory;
        $this->user_manager         = $user_manager;
        $this->project_manager      = $project_manager;
        $this->form_element_factory = $form_element_factory;
        $this->artifact_factory     = $artifact_factory;

        // Log everything in Backend
        // Only Warn and errors by email
        $backend_logger    = BackendLogger::getDefaultLogger(self::LOG_FILE);
        $this->mail_logger = new Tracker_Migration_MailLogger();
        $this->logger      = new Tracker_Migration_MigrationLogger(
            $backend_logger,
            $this->mail_logger
        );
        $this->creation_data_checker = $creation_data_checker;
    }

    /**
     * Launch the migration of a TV3 to a TV5
     *
     * @param $tracker_id
     * @param $name
     * @param $description
     * @param $short_name
     *
     * @return bool true if everything seems right
     */
    public function askForMigration(Project $project, $tracker_id, $name, $description, $short_name)
    {
        try {
            $this->creation_data_checker->checkAtProjectCreation((int) $project->getID(), $name, $short_name);
        } catch (TrackerIsInvalidException $exception) {
            return false;
        }

        $this->system_event_manager->queueTV3Migration($this->user_manager->getCurrentUser(), $project, $tracker_id, $name, $description, $short_name);
        return true;
    }

    public function migrate($username, $project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name)
    {
        $this->logger->info('-- Beginning of migration of tracker v3 ' . $tv3_id . ' to ' . $tracker_name . ' --');

        $user         = $this->user_manager->getUserByUserName($username);
        $tracker_id   = $this->createTrackerStructure($user, $project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name);
        $xml_path     = $this->exportTV3Data($tv3_id);
        $this->importArtifactsData($username, $tracker_id, $xml_path);
        unlink($xml_path);

        $this->logger->info('-- End of migration of tracker v3 ' . $tv3_id . ' to ' . $tracker_name . ' --');
        $this->mail_logger->sendMail($user, $this->project_manager->getProject($project_id), $tv3_id, $tracker_name);
    }

    public function isTrackerUnderMigration(Tracker $tracker)
    {
        return $this->system_event_manager->isThereAMigrationQueuedForTracker($tracker);
    }

    public function thereAreMigrationsOngoingForProject(Project $project)
    {
        return $this->system_event_manager->isThereAMigrationQueuedForProject($project);
    }

    private function importArtifactsData($username, $tracker_id, $xml_file_path)
    {
        $this->logger->info('--> Import into TV5 ');
        $this->user_manager->forceLogin($username);

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker) {
            $xml_import = $this->getXMLImporter();

            $xml_import->importFromFile($tracker, $xml_file_path, ForgeConfig::get('sys_data_dir') . DIRECTORY_SEPARATOR . 'trackerv3');
        }
        $this->logger->info('<-- TV5 imported ' . PHP_EOL);
    }

    private function getXMLImporter()
    {
        $fields_validator       = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($this->form_element_factory);
        $changeset_dao          = new Tracker_Artifact_ChangesetDao();
        $artifact_source_id_dao = new TrackerArtifactSourceIdDao();

        return new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $this->getArtifactCreator($fields_validator, $changeset_dao),
            $this->getChangesetCreator($fields_validator, $changeset_dao),
            $this->form_element_factory,
            new XMLImportHelper($this->user_manager),
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $this->logger,
            false,
            Tracker_ArtifactFactory::instance(),
            new NatureDao(),
            new XMLArtifactSourcePlatformExtractor(new Valid_HTTPURI(), $this->logger),
            new ExistingArtifactSourceIdFromTrackerExtractor($artifact_source_id_dao),
            $artifact_source_id_dao,
            new ExternalFieldsExtractor(EventManager::instance())
        );
    }

    private function getArtifactCreator(Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator, Tracker_Artifact_ChangesetDao $changeset_dao)
    {
        return new Tracker_ArtifactCreator(
            $this->artifact_factory,
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                new FieldsToBeSavedInSpecificOrderRetriever($this->form_element_factory),
                $changeset_dao,
                $this->artifact_factory,
                EventManager::instance(),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->form_element_factory),
                $this->logger
            ),
            new VisitRecorder(new RecentlyVisitedDao()),
            $this->logger,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        );
    }

    private function getChangesetCreator(Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator, Tracker_Artifact_ChangesetDao $changeset_dao)
    {
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();

        return new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($this->form_element_factory),
            $changeset_dao,
            $changeset_comment_dao,
            $this->artifact_factory,
            EventManager::instance(),
            ReferenceManager::instance(),
            new SourceOfAssociationCollectionBuilder(
                new SubmittedValueConvertor(
                    Tracker_ArtifactFactory::instance(),
                    new SourceOfAssociationDetector(
                        Tracker_HierarchyFactory::instance()
                    )
                ),
                Tracker_FormElementFactory::instance()
            ),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->form_element_factory),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
        );
    }

    private function exportTV3Data($tv3_id)
    {
        $this->logger->info('--> Export TV3 data ');
        $xml_path    = $this->generateTemporaryPath();
        $indent_xsl_path = $this->getIndentXSLResourcePath();
        $xml             = new DOMDocument("1.0", "UTF8");

        $dao                 = new ArtifactXMLExporterDao();
        $node_helper         = new ArtifactXMLNodeHelper($xml);
        $attachment_exporter = new ArtifactAttachmentXMLLinker($node_helper, $dao);

        $exporter = new ArtifactXMLExporter($dao, $attachment_exporter, $node_helper, $this->logger);
        $exporter->exportTrackerData($tv3_id);
        $this->logger->info('<-- TV3 data exported ' . PHP_EOL);

        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();
        $xsl = new DOMDocument();
        $xsl->load($indent_xsl_path);

        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xsl);

        $xml_string = $proc->transformToXML($xml);
        $xml_security->disableExternalLoadOfEntities();

        if (file_put_contents($xml_path, $xml_string) !== strlen($xml_string)) {
            throw new Exception('Something went wrong when writing tv3 xml in ' . $xml_path);
        }

        return $xml_path;
    }

    private function getIndentXSLResourcePath()
    {
        return ForgeConfig::get('codendi_utils_prefix') . self::INDENT_XSL_RESOURCE;
    }

    private function generateTemporaryPath()
    {
        // Generate a temporary File
        $file_path = tempnam(ForgeConfig::get('tmp_dir'), '');
        // Erase it but keep the path
        unlink($file_path);

        return $file_path;
    }

    private function createTrackerStructure(PFUser $user, $project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name)
    {
        $project = $this->project_manager->getProject($project_id);
        $this->logger->info('--> Migrate structure ');
        $new_tracker = $this->tracker_factory->createFromTV3($user, $tv3_id, $project, $tracker_name, $tracker_description, $tracker_short_name);
        if (! $new_tracker) {
            throw new Tracker_Exception_Migration_StructureCreationException($tracker_name, $tv3_id);
        }
        $this->logger->info('<-- Structure migrated ' . PHP_EOL);

        return $new_tracker->getId();
    }
}
