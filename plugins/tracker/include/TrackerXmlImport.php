<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Color\ColorName;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\CreateTrackerFromXMLEvent;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationNotificationsSettingsFromXmlBuilder;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Semantic\TrackerSemanticFactory;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Tracker\XML\Importer\BuildTrackersHierarchy;
use Tuleap\Tracker\Tracker\XML\Importer\CreateFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\FromXmlCreator;
use Tuleap\Tracker\Tracker\XML\Importer\GetInstanceFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\InstantiateTrackerFromXml;
use Tuleap\Tracker\Tracker\XML\Importer\OrderXmlTrackersByPriority;
use Tuleap\Tracker\Tracker\XML\Importer\TrackerFromXmlInstantiator;
use Tuleap\Tracker\Tracker\XML\Importer\TrackersHierarchyBuilder;
use Tuleap\Tracker\Tracker\XML\Importer\XmlTrackersByPriorityOrderer;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;
use Tuleap\Tracker\XML\Importer\ImportXMLProjectTrackerDone;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\XML\MappingsRegistry;
use Tuleap\XML\PHPCast;
use Tuleap\XML\SimpleXMLElementBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TrackerXmlImport
{
    public const DEFAULT_NOTIFICATIONS_LEVEL = 0;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var HierarchyDAO */
    private $hierarchy_dao;

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_Workflow_Trigger_RulesManager */
    private $trigger_rulesmanager;

    private $xml_fields_mapping = [];

    /**
     * @var array
     */
    private $renderers_xml_mapping     = [];
    private array $reports_xml_mapping = [];

    /** @var Tracker_Artifact_XMLImport */
    private $xml_import;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $artifact_links_usage_updater;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    /**
     * @var ExternalFieldsExtractor
     */
    private $external_fields_extractor;
    /**
     * @var TrackerXmlImportFeedbackCollector
     */
    private $feedback_collector;

    public function __construct(
        TrackerFactory $tracker_factory,
        EventManager $event_manager,
        HierarchyDAO $hierarchy_dao,
        private readonly GetInstanceFromXml $get_instance_from_xml,
        XML_RNGValidator $rng_validator,
        Tracker_Workflow_Trigger_RulesManager $trigger_rulesmanager,
        Tracker_Artifact_XMLImport $xml_import,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        \Psr\Log\LoggerInterface $logger,
        ArtifactLinksUsageUpdater $artifact_links_usage_updater,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
        private readonly TrackerXMLFieldMappingFromExistingTracker $existing_tracker_field_mapping,
        ExternalFieldsExtractor $external_fields_extractor,
        TrackerXmlImportFeedbackCollector $feedback_collector,
        private readonly CreateFromXml $from_xml_creator,
        private readonly InstantiateTrackerFromXml $instantiate_tracker_from_xml,
        private readonly OrderXmlTrackersByPriority $xml_trackers_by_priority_orderer,
        private readonly BuildTrackersHierarchy $hierarchy_builder,
    ) {
        $this->tracker_factory              = $tracker_factory;
        $this->event_manager                = $event_manager;
        $this->hierarchy_dao                = $hierarchy_dao;
        $this->rng_validator                = $rng_validator;
        $this->trigger_rulesmanager         = $trigger_rulesmanager;
        $this->xml_import                   = $xml_import;
        $this->user_finder                  = $user_finder;
        $this->logger                       = $logger;
        $this->artifact_links_usage_updater = $artifact_links_usage_updater;
        $this->artifact_links_usage_dao     = $artifact_links_usage_dao;
        $this->external_fields_extractor    = $external_fields_extractor;
        $this->feedback_collector           = $feedback_collector;
    }

    /**
     * @return TrackerXmlImport
     */
    public static function build(
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        ?\Psr\Log\LoggerInterface $logger = null,
    ) {
        $builder         = new Tracker_Artifact_XMLImportBuilder();
        $tracker_factory = TrackerFactory::instance();

        $logger = new WrapperLogger(
            $logger ?? new \Psr\Log\NullLogger(),
            'TrackerXMLImport'
        );

        $artifact_links_usage_dao     = new ArtifactLinksUsageDao();
        $artifact_links_usage_updater = new ArtifactLinksUsageUpdater($artifact_links_usage_dao);
        $event_manager                = EventManager::instance();
        $ugroup_manager               = new UGroupManager();

        $form_element_factory = Tracker_FormElementFactory::instance();

        $feedback_collector = new TrackerXmlImportFeedbackCollector();

        $get_instance_from_xml = new GetInstanceFromXml(
            $tracker_factory,
            Tracker_CannedResponseFactory::instance(),
            $form_element_factory,
            $user_finder,
            $feedback_collector,
            TrackerSemanticFactory::instance(),
            new Tracker_RuleFactory(
                new Tracker_RuleDao()
            ),
            Tracker_ReportFactory::instance(),
            WorkflowFactory::instance(),
            new WebhookFactory(new WebhookDao()),
            new UGroupRetrieverWithLegacy($ugroup_manager),
            $logger,
        );

        $rng_validator = new XML_RNGValidator();

        $tracker_creation_data_checker = TrackerCreationDataChecker::build();


        $external_fields_extractor = new ExternalFieldsExtractor($event_manager);
        $from_xml_creator          = new FromXmlCreator(
            $tracker_factory,
            $form_element_factory,
            $get_instance_from_xml,
            $rng_validator,
            $external_fields_extractor,
            $tracker_creation_data_checker,
            new TrackerCreationNotificationsSettingsFromXmlBuilder(),
            $feedback_collector,
            $logger,
        );
        return new TrackerXmlImport(
            $tracker_factory,
            $event_manager,
            new HierarchyDAO(),
            $get_instance_from_xml,
            $rng_validator,
            $tracker_factory->getTriggerRulesManager(),
            $builder->build(
                $user_finder,
                $logger
            ),
            $user_finder,
            $logger,
            $artifact_links_usage_updater,
            $artifact_links_usage_dao,
            new TrackerXMLFieldMappingFromExistingTracker(),
            $external_fields_extractor,
            $feedback_collector,
            $from_xml_creator,
            new TrackerFromXmlInstantiator(
                $tracker_factory,
                $form_element_factory,
                $from_xml_creator,
                $feedback_collector,
                $logger,
            ),
            new XmlTrackersByPriorityOrderer(),
            new TrackersHierarchyBuilder(),
        );
    }

    /**
     * @return Tracker[]|void
     * @throws TrackerFromXmlException
     * @throws TrackerFromXmlImportCannotBeCreatedException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     */
    public function import(
        ImportConfig $configuration,
        Project $project,
        SimpleXMLElement $xml_input,
        MappingsRegistry $registery,
        string $extraction_path,
        PFUser $user,
    ) {
        if (! $xml_input->trackers) {
            return;
        }

        $partial_element = SimpleXMLElementBuilder::buildSimpleXMLElementToLoadHugeFiles((string) $xml_input->asXml());
        $this->external_fields_extractor->extractExternalFieldFromProjectElement($partial_element);
        $this->rng_validator->validate(
            $partial_element->trackers,
            __DIR__ . '/../resources/trackers.rng'
        );

        $tracker_import_config = new TrackerXmlImportConfig($user, new \DateTimeImmutable(), MoveImportConfig::buildForRegularImport(), false);

        $this->activateArtlinkV2($project, $xml_input->trackers);

        $this->xml_fields_mapping = [];
        $created_trackers_mapping = [];
        $created_trackers_objects = [];
        $artifacts_id_mapping     = new Tracker_XML_Importer_ArtifactImportedMapping();
        $changeset_id_mapping     = new ImportedChangesetMapping();
        $url_mapping              = new CreatedFileURLMapping();

        $ordered_xml_trackers = $this->xml_trackers_by_priority_orderer->getAllXmlTrackersOrderedByPriority($xml_input);

        foreach ($ordered_xml_trackers as $xml_tracker_id => $ordered_xml_tracker) {
            $tracker_created = $this->instantiate_tracker_from_xml->instantiateTrackerFromXml(
                $project,
                $ordered_xml_tracker,
                $configuration,
                $created_trackers_mapping,
                $this->existing_tracker_field_mapping,
                $this->xml_fields_mapping,
                $this->reports_xml_mapping,
                $this->renderers_xml_mapping,
            );

            $created_trackers_objects[$xml_tracker_id] = $tracker_created;
            $created_trackers_mapping                  = $created_trackers_mapping + [(string) $xml_tracker_id => $tracker_created->getId()];
            $registery->addReference($xml_tracker_id, $tracker_created->getId());
        }

        foreach ($this->renderers_xml_mapping as $xml_reference => $renderer_xml_mapping) {
            $registery->addReference($xml_reference, $renderer_xml_mapping);
        }
        foreach ($this->reports_xml_mapping as $xml_reference => $report_xml_mapping) {
            $registery->addReference($xml_reference, $report_xml_mapping);
        }

        $xml_field_values_mapping = new TrackerXmlFieldsMapping_FromAnotherPlatform($this->xml_fields_mapping);

        $created_artifacts = $this->importBareArtifacts(
            $ordered_xml_trackers,
            $created_trackers_objects,
            $artifacts_id_mapping,
            $tracker_import_config
        );

        $this->importChangesets(
            $ordered_xml_trackers,
            $created_trackers_objects,
            $extraction_path,
            $xml_field_values_mapping,
            $artifacts_id_mapping,
            $url_mapping,
            $created_artifacts,
            $changeset_id_mapping,
            $tracker_import_config
        );

        // Deal with artifact link types after changesets import to keep the history of types
        $this->disableArtifactLinkTypes($xml_input, $project);

        if (
            $this->artifact_links_usage_dao->isTypeDisabledInProject(
                $project->getID(),
                ArtifactLinkField::TYPE_IS_CHILD
            )
        ) {
            $this->logger->warning('Artifact link type _is_child is disabled, skipping the hierarchy');
        } else {
            $this->importHierarchy($xml_input, $created_trackers_mapping);
        }

        if (isset($xml_input->trackers->triggers)) {
            $this->trigger_rulesmanager->createFromXML($xml_input->trackers->triggers, $this->xml_fields_mapping);
        }

        $event = new ImportXMLProjectTrackerDone(
            $project,
            $xml_input,
            $created_trackers_mapping,
            $this->xml_fields_mapping,
            $registery,
            $artifacts_id_mapping,
            $changeset_id_mapping,
            $extraction_path,
            $this->logger,
            $xml_field_values_mapping,
            $this->user_finder,
            $created_trackers_objects,
            $user
        );
        $this->event_manager->processEvent($event);

        $this->event_manager->processEvent(
            Event::IMPORT_COMPAT_REF_XML,
            [
                'logger'          => $this->logger,
                'created_refs'    => [
                    'tracker'  => $created_trackers_mapping,
                    'artifact' => $artifacts_id_mapping->getMapping(),
                ],
                'service_name'    => 'tracker',
                'xml_content'     => $xml_input->trackers->references,
                'project'         => $project,
                'configuration'   => $configuration,
            ]
        );

        return $created_trackers_mapping;
    }

    private function disableArtifactLinkTypes(SimpleXMLElement $xml_input, Project $project)
    {
        if (! $xml_input->natures) {
            return;
        }

        foreach ($xml_input->natures->nature as $xml_type) {
            assert($xml_type instanceof SimpleXMLElement);
            $is_used = ! isset($xml_type['is_used']) || PHPCast::toBoolean($xml_type['is_used']) === true;

            if (! $is_used) {
                $type_name = (string) $xml_type;

                $event = new XMLImportArtifactLinkTypeCanBeDisabled($project, $type_name);
                $this->event_manager->processEvent($event);

                if ($this->typeCanBeDisabled($event)) {
                    $this->logger->info("Artifact link type $type_name will be deactivated.");
                    $this->artifact_links_usage_dao->disableTypeInProject($project->getID(), $type_name);
                } else {
                    $this->logger->warning($event->getMessage());
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function typeCanBeDisabled(XMLImportArtifactLinkTypeCanBeDisabled $event)
    {
        return ! $event->doesPluginCheckedTheType() ||
            ($event->doesPluginCheckedTheType() && $event->canTypeBeUnused());
    }

    /**
     * @return string
     * @throws Tracker_Exception
     */
    public function collectErrorsWithoutImporting(Project $project, SimpleXMLElement $xml_input)
    {
        if (! $xml_input->trackers) {
            return '';
        }
        $partial_element = SimpleXMLElementBuilder::buildSimpleXMLElementToLoadHugeFiles((string) $xml_input->asXml());
        $this->external_fields_extractor->extractExternalFieldFromProjectElement($partial_element);
        $this->rng_validator->validate($partial_element->trackers, __DIR__ . '/../resources/trackers.rng');

        $xml_trackers = $this->xml_trackers_by_priority_orderer->getAllXmlTrackersOrderedByPriority($xml_input);
        $trackers     = [];

        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            $name        = (string) $xml_tracker->name;
            $description = (string) $xml_tracker->description;
            $item_name   = (string) $xml_tracker->item_name;
            $trackers[]  = $this->get_instance_from_xml->getInstanceFromXML(
                $xml_tracker,
                $project,
                $name,
                $description,
                $item_name,
                ColorName::default()->value,
                [],
                $this->xml_fields_mapping,
                $this->reports_xml_mapping,
                $this->renderers_xml_mapping,
            );
        }

        $trackers_name_error = $this->tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            $trackers,
            $project->getID()
        );

        $errors = '';

        if (! empty($trackers_name_error)) {
            $list_trackers_name = implode(', ', $trackers_name_error);
            $errors             = sprintf(dgettext('tuleap-tracker', 'The following trackers cannot be imported due to invalid data, name or short name already in use: %1$s'), $list_trackers_name);
        }

        return $errors;
    }

    private function activateArtlinkV2(Project $project, SimpleXMLElement $xml_element)
    {
        $use_natures = $xml_element['use-natures'];
        if ($use_natures == 'true') {
            if ($this->artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($project)) {
                $this->logger->info('This project already uses artifact links nature feature.');
            } else {
                $this->artifact_links_usage_updater->forceUsageOfArtifactLinkTypes($project);
                $this->logger->info('Artifact links nature feature is now active.');
            }
        } elseif ($use_natures == 'false') {
            if (! $this->artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($project)) {
                $this->logger->warning('This project will not be able to use artifact links nature feature.');
            }
        } else {
            $this->artifact_links_usage_updater->forceUsageOfArtifactLinkTypes($project);
            $this->logger->info("No attribute 'use-natures' found. By default, projects use the typed artifact links");
        }
    }

    /**
     * @return array of created artifacts
     * @throws Tracker_Artifact_Exception_XMLImportException
     */
    private function importBareArtifacts(
        array $xml_trackers,
        array $created_trackers_objects,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping,
        TrackerXmlImportConfig $tracker_import_config,
    ) {
        $created_artifacts = [];
        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            if (isset($xml_tracker->artifacts)) {
                $created_artifacts[$xml_tracker_id] = $this->xml_import->importBareArtifactsFromXML(
                    $created_trackers_objects[$xml_tracker_id],
                    $xml_tracker->artifacts,
                    $artifacts_id_mapping,
                    $tracker_import_config
                );
            }
        }
        return $created_artifacts;
    }

    /**
     * @throws Tracker_Artifact_Exception_XMLImportException
     */
    private function importChangesets(
        array $xml_trackers,
        array $created_trackers_objects,
        $extraction_path,
        TrackerXmlFieldsMapping_FromAnotherPlatform $xml_mapping,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping,
        CreatedFileURLMapping $url_mapping,
        array $created_artifacts,
        ImportedChangesetMapping $changeset_id_mapping,
        TrackerXmlImportConfig $tracker_import_config,
    ): void {
        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            if (isset($xml_tracker->artifacts)) {
                $this->xml_import->importArtifactChangesFromXML(
                    $created_trackers_objects[$xml_tracker_id],
                    $xml_tracker->artifacts,
                    $extraction_path,
                    $xml_mapping,
                    $artifacts_id_mapping,
                    $url_mapping,
                    $created_artifacts[$xml_tracker_id],
                    $changeset_id_mapping,
                    $tracker_import_config
                );
            }
        }
    }

    private function importHierarchy(SimpleXMLElement $xml_input, array $created_trackers_list)
    {
        $all_hierarchies = [];
        foreach ($this->xml_trackers_by_priority_orderer->getAllXmlTrackersOrderedByPriority($xml_input) as $xml_tracker) {
            $all_hierarchies = $this->hierarchy_builder->buildTrackersHierarchy(
                $all_hierarchies,
                $xml_tracker,
                $created_trackers_list,
            );
        }

        $this->storeHierarchyInDB($all_hierarchies);
    }

    /**
     * @return Tracker|null
     * @throws TrackerFromXmlException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     */
    public function createFromXMLFile(Project $project, string $filepath)
    {
        $tracker_xml = $this->loadXmlFile($filepath);
        if (! $tracker_xml) {
            return null;
        }

        try {
            $name        = (string) $tracker_xml->name;
            $description = (string) $tracker_xml->description;
            $item_name   = (string) $tracker_xml->item_name;

            return $this->from_xml_creator->createFromXML(
                $tracker_xml,
                $project,
                $name,
                $description,
                $item_name,
                ColorName::default()->value,
                [],
                $this->xml_fields_mapping,
                $this->reports_xml_mapping,
                $this->renderers_xml_mapping,
            );
        } catch (\Tuleap\Tracker\TrackerIsInvalidException $exception) {
            $this->feedback_collector->addErrors($exception->getTranslatedMessage());
            $this->feedback_collector->displayErrors($this->logger);
            return null;
        }
    }

    /**
     * @throws TrackerFromXmlException
     * @throws Tracker_Exception
     * @throws XML_ParseException
     * @throws \Tuleap\Tracker\TrackerIsInvalidException
     */
    public function createFromXMLFileWithInfo(
        Project $project,
        string $filepath,
        string $name,
        string $description,
        string $item_name,
        ?string $color,
    ): Tracker {
        $tracker_xml = $this->loadXmlFile($filepath);
        if (! $tracker_xml) {
            throw TrackerIsInvalidException::invalidXmlFile();
        }
        $event = new CreateTrackerFromXMLEvent($project, $tracker_xml);
        $this->event_manager->processEvent($event);

        return $this->from_xml_creator->createFromXML(
            $tracker_xml,
            $project,
            $name,
            $description,
            $item_name,
            $color,
            [],
            $this->xml_fields_mapping,
            $this->reports_xml_mapping,
            $this->renderers_xml_mapping,
        );
    }

    /**
     *
     * @param array $all_hierarchies
     *
     * Stores in database the hierarchy between created trackers
     */
    public function storeHierarchyInDB(array $all_hierarchies)
    {
        foreach ($all_hierarchies as $parent_id => $hierarchy) {
            $this->hierarchy_dao->updateChildren($parent_id, $hierarchy);
        }
    }

    /**
     * @return SimpleXMLElement|false
     */
    protected function loadXmlFile(string $filepath)
    {
        return \simplexml_load_string(\file_get_contents($filepath));
    }
}
