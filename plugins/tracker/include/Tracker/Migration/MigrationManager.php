<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
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

use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Migration\KeepReverseCrossReferenceDAO;
use Tuleap\Tracker\Migration\LegacyTrackerMigrationDao;
use Tuleap\Tracker\TrackerIsInvalidException;

class Tracker_Migration_MigrationManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const LOG_FILE = 'tv3_tv5_migration_syslog';

    /** @var  Tracker_SystemEventManager */
    private $system_event_manager;

    /** @var  TrackerFactory */
    private $tracker_factory;

    /** @var  UserManager */
    private $user_manager;

    /** @var  ProjectManager */
    private $project_manager;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var Tracker_Migration_MailLogger */
    private $mail_logger;
    /**
     * @var TrackerCreationDataChecker
     */
    private $creation_data_checker;

    /**
     * @var LegacyTrackerMigrationDao
     */
    private $legacy_tracker_migration_dao;
    /**
     * @var KeepReverseCrossReferenceDAO
     */
    private $keep_reverse_cross_reference_dao;

    public function __construct(
        Tracker_SystemEventManager $system_event_manager,
        TrackerFactory $tracker_factory,
        UserManager $user_manager,
        ProjectManager $project_manager,
        TrackerCreationDataChecker $creation_data_checker,
        LegacyTrackerMigrationDao $legacy_tracker_migration_dao,
        KeepReverseCrossReferenceDAO $keep_reverse_cross_reference_dao,
        Tracker_Migration_MailLogger $mail_logger,
        Tracker_Migration_MigrationLogger $logger,
    ) {
        $this->system_event_manager             = $system_event_manager;
        $this->tracker_factory                  = $tracker_factory;
        $this->user_manager                     = $user_manager;
        $this->project_manager                  = $project_manager;
        $this->creation_data_checker            = $creation_data_checker;
        $this->legacy_tracker_migration_dao     = $legacy_tracker_migration_dao;
        $this->keep_reverse_cross_reference_dao = $keep_reverse_cross_reference_dao;

        // Log everything in Backend
        // Only Warn and errors by email
        $this->mail_logger = $mail_logger;
        $this->logger      = $logger;
    }

    public function migrate(
        $username,
        $project_id,
        $tv3_id,
        $tracker_name,
        $tracker_description,
        $tracker_short_name,
        bool $keep_original_ids,
    ): void {
        $this->logger->info('-- Beginning of migration of tracker v3 ' . $tv3_id . ' to ' . $tracker_name . ' --');

        if (
            $keep_original_ids === true &&
            $this->legacy_tracker_migration_dao->isLegacyTrackerAlreadyMigratedWithOriginalIds((int) $tv3_id)
        ) {
            $message = "Legacy tracker v3 #$tv3_id was previously migrated with original ids.";
            $this->logger->error($message);
            $this->logger->error('Skipping;');
            throw new RuntimeException($message);
        }

        $user = $this->user_manager->getUserByUserName($username);
        if (! $user) {
            $this->logger->error('Can not find the user for TV3 migration!!!');
            $this->logger->error('Skipping;');
            throw new RuntimeException("User $username not found.");
        }
        $tracker_id = $this->createTrackerStructure($user, $project_id, $tv3_id, $tracker_name, $tracker_description, $tracker_short_name);
        $xml_path   = $this->exportTV3Data($tv3_id);
        $this->importArtifactsData($username, $tracker_id, $xml_path, $user, $keep_original_ids);
        unlink($xml_path);

        if ($keep_original_ids === true) {
            $this->legacy_tracker_migration_dao->flagLegacyTrackerMigratedWithOriginalIds(
                (int) $tv3_id
            );
            $this->keep_reverse_cross_reference_dao->createCrossReferenceFromTrackerIDs((int) $tv3_id, $tracker_id);
        }

        $this->logger->info('-- End of migration of tracker v3 ' . $tv3_id . ' to ' . $tracker_name . ' --');
        $this->mail_logger->sendMail($user, $this->project_manager->getProject($project_id), $tv3_id, $tracker_name);
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
    public function askForMigration(Project $project, $tracker_id, $name, $description, $short_name, bool $keep_original_ids)
    {
        try {
            $this->creation_data_checker->checkAtProjectCreation((int) $project->getID(), $name, $short_name);
        } catch (TrackerIsInvalidException $exception) {
            return false;
        }

        $this->system_event_manager->queueTV3Migration(
            $this->user_manager->getCurrentUser(),
            $project,
            $tracker_id,
            $name,
            $description,
            $short_name,
            $keep_original_ids
        );

        return true;
    }

    public function isTrackerUnderMigration(Tracker $tracker)
    {
        return $this->system_event_manager->isThereAMigrationQueuedForTracker($tracker);
    }

    public function thereAreMigrationsOngoingForProject(Project $project)
    {
        return $this->system_event_manager->isThereAMigrationQueuedForProject($project);
    }

    private function importArtifactsData(
        $username,
        $tracker_id,
        $xml_file_path,
        PFUser $user,
        bool $keep_original_ids,
    ): void {
        $this->logger->info('--> Import into TV5 ');
        $this->user_manager->forceLogin($username);

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker) {
            $xml_import = $this->getXMLImporter();

            $xml                = \simplexml_load_string(\file_get_contents($xml_file_path));
            $xml_file_path      = "";
            $xml_field_mapping  = new TrackerXmlFieldsMapping_InSamePlatform();
            $url_mapping        = new CreatedFileURLMapping();
            $date               = new DateTimeImmutable();
            $tracker_xml_config = new TrackerXmlImportConfig(
                $user,
                $date,
                MoveImportConfig::buildForRegularImport(),
                $keep_original_ids,
            );

            $xml_import->importFromXML(
                $tracker,
                $xml,
                $xml_file_path,
                $xml_field_mapping,
                $url_mapping,
                $tracker_xml_config
            );
        }

        $this->logger->info('<-- TV5 imported ' . PHP_EOL);
    }

    private function getXMLImporter(): Tracker_Artifact_XMLImport
    {
        $builder = new Tracker_Artifact_XMLImportBuilder();
        return $builder->build(
            new XMLImportHelper($this->user_manager),
            $this->logger
        );
    }

    private function exportTV3Data($tv3_id)
    {
        $this->logger->info('--> Export TV3 data ');
        $xml_path = $this->generateTemporaryPath();
        $xml      = new DOMDocument("1.0", "UTF8");

        $dao                 = new ArtifactXMLExporterDao();
        $node_helper         = new ArtifactXMLNodeHelper($xml);
        $attachment_exporter = new ArtifactAttachmentXMLLinker($node_helper, $dao);

        $exporter = new ArtifactXMLExporter($dao, $attachment_exporter, $node_helper, $this->logger);
        $exporter->exportTrackerData($tv3_id);
        $this->logger->info('<-- TV3 data exported ' . PHP_EOL);

        $xsl = new DOMDocument();
        $xsl->loadXML(file_get_contents(__DIR__ . '/../../../../../src/utils/xml/indent.xsl'));

        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xsl);

        $xml_string = $proc->transformToXML($xml);

        if (file_put_contents($xml_path, $xml_string) !== strlen($xml_string)) {
            throw new Exception('Something went wrong when writing tv3 xml in ' . $xml_path);
        }

        return $xml_path;
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
