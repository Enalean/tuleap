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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\TrackerFromXmlImportCannotBeUpdatedException;
use Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\XML\MappingsRegistry;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\XML\PHPCast;

class TrackerXmlImport
{
    /**
     * Add attributes to tracker
     *
     * Parameters:
     *  - xml_element: input SimpleXMLElement
     *  - tracker_id:  input int
     *  - project:     input Project
     *  - logger:      output Logger
     */
    public const ADD_PROPERTY_TO_TRACKER = 'add_property_to_tracker';

    public const XML_PARENT_ID_EMPTY = "0";

    public const DEFAULT_NOTIFICATIONS_LEVEL = 0;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var HierarchyDAO */
    private $hierarchy_dao;

    /** @var Tracker_CannedResponseFactory */
    private $canned_response_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Tracker_SemanticFactory */
    private $semantic_factory;

    /** @var Tracker_RuleFactory */
    private $rule_factory;

    /** @var Tracker_ReportFactory */
    private $report_factory;

    /** @var WorkflowFactory */
    private $workflow_factory;

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_Workflow_Trigger_RulesManager */
    private $trigger_rulesmanager;

    private $xml_fields_mapping = array();

    /**
     * @var array
     */
    private $renderers_xml_mapping = [];

    /** @var Tracker_Artifact_XMLImport */
    private $xml_import;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var Logger */
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
     * @var WebhookFactory
     */
    private $webhook_factory;

    /**
     * @var TrackerXMLFieldMappingFromExistingTracker
     */
    private $existing_tracker_field_mapping;

    public function __construct(
        TrackerFactory $tracker_factory,
        EventManager $event_manager,
        HierarchyDAO $hierarchy_dao,
        Tracker_CannedResponseFactory $canned_response_factory,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_SemanticFactory $semantic_factory,
        Tracker_RuleFactory $rule_factory,
        Tracker_ReportFactory $report_factory,
        WorkflowFactory $workflow_factory,
        XML_RNGValidator $rng_validator,
        Tracker_Workflow_Trigger_RulesManager $trigger_rulesmanager,
        Tracker_Artifact_XMLImport $xml_import,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        UGroupManager $ugroup_manager,
        Logger $logger,
        ArtifactLinksUsageUpdater $artifact_links_usage_updater,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
        WebhookFactory $webhook_factory,
        TrackerXMLFieldMappingFromExistingTracker $tracker_XML_field_mapping_from_existing_tracker
    ) {
        $this->tracker_factory              = $tracker_factory;
        $this->event_manager                = $event_manager;
        $this->hierarchy_dao                = $hierarchy_dao;
        $this->canned_response_factory      = $canned_response_factory;
        $this->formelement_factory          = $formelement_factory;
        $this->semantic_factory             = $semantic_factory;
        $this->rule_factory                 = $rule_factory;
        $this->report_factory               = $report_factory;
        $this->workflow_factory             = $workflow_factory;
        $this->rng_validator                = $rng_validator;
        $this->trigger_rulesmanager         = $trigger_rulesmanager;
        $this->xml_import                   = $xml_import;
        $this->user_finder                  = $user_finder;
        $this->ugroup_manager               = $ugroup_manager;
        $this->logger                       = $logger;
        $this->artifact_links_usage_updater = $artifact_links_usage_updater;
        $this->artifact_links_usage_dao     = $artifact_links_usage_dao;
        $this->webhook_factory              = $webhook_factory;
        $this->existing_tracker_field_mapping = $tracker_XML_field_mapping_from_existing_tracker;
    }

    /**
     * @param \User\XML\Import\IFindUserFromXMLReference $user_finder
     * @param Logger|null $logger
     * @return TrackerXmlImport
     */
    public static function build(
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        ?Logger $logger = null
    ) {
        $builder         = new Tracker_Artifact_XMLImportBuilder();
        $tracker_factory = TrackerFactory::instance();

        $logger = $logger === null ? new Log_NoopLogger() : $logger;

        $artifact_links_usage_dao     = new ArtifactLinksUsageDao();
        $artifact_links_usage_updater = new ArtifactLinksUsageUpdater($artifact_links_usage_dao);

        return new TrackerXmlImport(
            $tracker_factory,
            EventManager::instance(),
            new HierarchyDAO(),
            Tracker_CannedResponseFactory::instance(),
            Tracker_FormElementFactory::instance(),
            Tracker_SemanticFactory::instance(),
            new Tracker_RuleFactory(
                new Tracker_RuleDao()
            ),
            Tracker_ReportFactory::instance(),
            WorkflowFactory::instance(),
            new XML_RNGValidator(),
            $tracker_factory->getTriggerRulesManager(),
            $builder->build(
                $user_finder,
                $logger
            ),
            $user_finder,
            new UGroupManager(),
            new WrapperLogger($logger, 'TrackerXMLImport'),
            $artifact_links_usage_updater,
            $artifact_links_usage_dao,
            new WebhookFactory(new WebhookDao()),
            new TrackerXMLFieldMappingFromExistingTracker()
        );
    }

    /**
     *
     * @return array Array of SimpleXmlElement with each tracker
     */
    protected function getAllXmlTrackers(SimpleXMLElement $xml_input)
    {
        $tracker_list = array();
        foreach ($xml_input->trackers->tracker as $xml_tracker) {
            $tracker_list[$this->getXmlTrackerAttribute($xml_tracker, 'id')] = $xml_tracker;
        }
        return $tracker_list;
    }

    /**
     *
     * @param SimpleXMLElement $xml_tracker
     * @param type $attribute_name
     * @return String | bool the attribute value in String, False if this attribute does not exist
     */
    private function getXmlTrackerAttribute(SimpleXMLElement $xml_tracker, $attribute_name)
    {
        $tracker_attributes = $xml_tracker->attributes();
        if (! $tracker_attributes[$attribute_name]) {
            return false;
        }
        return (String) $tracker_attributes[$attribute_name];
    }

    /**
     * @param ImportConfig $configuration
     * @param Project $project
     * @param SimpleXMLElement $xml_input
     *
     * @param MappingsRegistry $registery
     * @param $extraction_path
     * @return Tracker[]|void
     * @throws TrackerFromXmlException
     * @throws TrackerFromXmlImportCannotBeCreatedException
     * @throws Tracker_Exception
     */
    public function import(
        ImportConfig $configuration,
        Project $project,
        SimpleXMLElement $xml_input,
        MappingsRegistry $registery,
        $extraction_path
    ) {
        if (! $xml_input->trackers) {
            return;
        }

        $this->rng_validator->validate($xml_input->trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');

        $this->activateArtlinkV2($project, $xml_input->trackers);

        $this->xml_fields_mapping = array();
        $created_trackers_mapping = array();
        $created_trackers_objects = array();
        $artifacts_id_mapping     = new Tracker_XML_Importer_ArtifactImportedMapping();
        $url_mapping              = new CreatedFileURLMapping();

        $xml_trackers = $this->getAllXmlTrackers($xml_input);

        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            $tracker_created = $this->instantiateTrackerFromXml($project, $xml_tracker, $configuration);
            $created_trackers_objects[$xml_tracker_id] = $tracker_created;
            $created_trackers_mapping = array_merge($created_trackers_mapping, array($xml_tracker_id => $tracker_created->getId()));
        }

        foreach ($this->renderers_xml_mapping as $xml_reference => $renderer_xml_mapping) {
            assert($renderer_xml_mapping instanceof Tracker_Report_Renderer);
            $registery->addReference($xml_reference, $renderer_xml_mapping);
        }

        $xml_mapping = new TrackerXmlFieldsMapping_FromAnotherPlatform($this->xml_fields_mapping);

        $created_artifacts = $this->importBareArtifacts(
            $xml_trackers,
            $created_trackers_objects,
            $extraction_path,
            $xml_mapping,
            $artifacts_id_mapping,
            $configuration
        );

        $this->importChangesets(
            $xml_trackers,
            $created_trackers_objects,
            $extraction_path,
            $xml_mapping,
            $artifacts_id_mapping,
            $url_mapping,
            $created_artifacts,
            $configuration
        );

        // Deal with artifact link types after changesets import to keep the history of types
        $this->disableArtifactLinkTypes($xml_input, $project);

        if ($this->artifact_links_usage_dao->isTypeDisabledInProject(
            $project->getID(),
            Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
        )) {
            $this->logger->warn('Artifact link type _is_child is disabled, skipping the hierarchy');
        } else {
            $this->importHierarchy($xml_input, $created_trackers_mapping);
        }

        if (isset($xml_input->trackers->triggers)) {
            $this->trigger_rulesmanager->createFromXML($xml_input->trackers->triggers, $this->xml_fields_mapping);
        }

        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT_TRACKER_DONE,
            array(
                'project'             => $project,
                'xml_content'         => $xml_input,
                'mapping'             => $created_trackers_mapping,
                'field_mapping'       => $this->xml_fields_mapping,
                'mappings_registery'  => $registery,
                'artifact_id_mapping' => $artifacts_id_mapping,
                'extraction_path'     => $extraction_path,
                'logger'              => $this->logger,
                'value_mapping'       => $xml_mapping
            )
        );

        $this->event_manager->processEvent(
            Event::IMPORT_COMPAT_REF_XML,
            array(
                'logger'          => $this->logger,
                'created_refs'    => array('tracker'  => $created_trackers_mapping,
                                           'artifact' => $artifacts_id_mapping->getMapping()),
                'service_name'    => 'tracker',
                'xml_content'     => $xml_input->trackers->references,
                'project'         => $project,
                'configuration'   => $configuration,
            )
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
                    $this->logger->warn($event->getMessage());
                }
            }
        }
    }

    /**
     * @param XMLImportArtifactLinkTypeCanBeDisabled $event
     * @return bool
     */
    private function typeCanBeDisabled(XMLImportArtifactLinkTypeCanBeDisabled $event)
    {
        return ! $event->doesPluginCheckedTheType() ||
            ($event->doesPluginCheckedTheType() && $event->canTypeBeUnused());
    }

    /**
     * @param Project $project
     * @param SimpleXMLElement $xml_input
     * @return string
     * @throws Tracker_Exception
     */
    public function collectErrorsWithoutImporting(Project $project, SimpleXMLElement $xml_input)
    {
        if (! $xml_input->trackers) {
            return '';
        }

        $this->rng_validator->validate($xml_input->trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');

        $xml_trackers = $this->getAllXmlTrackers($xml_input);
        $trackers = array();

        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            $name = (string) $xml_tracker->name;
            $description = (string) $xml_tracker->description;
            $item_name = (string) $xml_tracker->item_name;
            $trackers[] = $this->getInstanceFromXML(
                $xml_tracker,
                $project,
                $name,
                $description,
                $item_name,
                new TrackerXmlImportFeedbackCollector($this->logger),
            );
        }

        $trackers_name_error = $this->tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo(
            $trackers,
            $project->getID()
        );

        $errors = '';

        if (! empty($trackers_name_error)) {
            $list_trackers_name = implode(', ', $trackers_name_error);
            $errors = $GLOBALS['Language']->getText(
                'plugin_tracker_common_type',
                'trackers_cant_be_imported',
                array($list_trackers_name)
            );
        }

        return $errors;
    }

    private function activateArtlinkV2(Project $project, SimpleXMLElement $xml_element)
    {
        $use_natures = $xml_element['use-natures'];
        if ($use_natures == 'true') {
            if ($this->artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($project)) {
                $this->logger->info("This project already uses artifact links nature feature.");
            } else {
                $this->artifact_links_usage_updater->forceUsageOfArtifactLinkTypes($project);
                $this->logger->info("Artifact links nature feature is now active.");
            }
        } elseif ($use_natures == 'false') {
            if ($this->artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($project)) {
                $this->artifact_links_usage_updater->forceDeactivationOfArtifactLinkTypes($project);
                $this->logger->warn("This project used artifact links nature. It is now deactivated!!!");
            } else {
                $this->logger->warn("This project will not be able to use artifact links nature feature.");
            }
        } else {
            $this->artifact_links_usage_updater->forceUsageOfArtifactLinkTypes($project);
            $this->logger->info("No attribute 'use-natures' found. By default, projects use the typed artifact links");
        }
    }

    /**
     * @param array $xml_trackers
     * @param array $created_trackers_objects
     * @param $extraction_path
     * @param TrackerXmlFieldsMapping_FromAnotherPlatform $xml_mapping
     * @param Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping
     * @param ImportConfig $configuration
     * @return array of created artifacts
     * @throws Tracker_Artifact_Exception_XMLImportException
     */
    private function importBareArtifacts(
        array $xml_trackers,
        array $created_trackers_objects,
        $extraction_path,
        TrackerXmlFieldsMapping_FromAnotherPlatform $xml_mapping,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_id_mapping,
        ImportConfig $configuration
    ) {
        $created_artifacts = array();
        foreach ($xml_trackers as $xml_tracker_id => $xml_tracker) {
            if (isset($xml_tracker->artifacts)) {
                $created_artifacts[$xml_tracker_id] = $this->xml_import->importBareArtifactsFromXML(
                    $created_trackers_objects[$xml_tracker_id],
                    $xml_tracker->artifacts,
                    $extraction_path,
                    $xml_mapping,
                    $artifacts_id_mapping,
                    $configuration
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
        ImportConfig $configuration
    ) {
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
                    $configuration
                );
            }
        }
    }

    private function importHierarchy(SimpleXMLElement $xml_input, array $created_trackers_list)
    {
        $all_hierarchies = array();
        foreach ($this->getAllXmlTrackers($xml_input) as $xml_tracker) {
            $all_hierarchies = $this->buildTrackersHierarchy($all_hierarchies, $xml_tracker, $created_trackers_list);
        }

        $this->storeHierarchyInDB($all_hierarchies);
    }

    /**
     *
     * @param Project $project
     * @param SimpleXMLElement $xml_tracker
     * @param ImportConfig $configuration
     * @return Tracker the link between xml id and new id given by Tuleap
     *
     * @throws TrackerFromXmlException
     * @throws TrackerFromXmlImportCannotBeCreatedException
     * @throws Tracker_Exception
     */
    private function instantiateTrackerFromXml(
        Project $project,
        SimpleXMLElement $xml_tracker,
        ImportConfig $configuration
    ) {
        $tracker_existing = $this->getTrackerToReUse($project, $xml_tracker, $configuration);
        if ($tracker_existing !== null) {
            return $tracker_existing;
        }

        if ($configuration->isUpdate()) {
            $tracker_created = $this->updateFromXML($project, $xml_tracker);
        } else {
            $tracker_created = $this->createFromXML(
                $xml_tracker,
                $project,
                (String)$xml_tracker->name,
                (String)$xml_tracker->description,
                (String)$xml_tracker->item_name
            );

            if (! $tracker_created) {
                throw new TrackerFromXmlImportCannotBeCreatedException((String)$xml_tracker->name);
            }
        }
        return $tracker_created;
    }

    /**
     * @return null|Tracker
     */
    private function getTrackerToReUse(
        Project $project,
        SimpleXMLElement $xml_tracker,
        ImportConfig $configuration
    ) {
        foreach ($configuration->getExtraConfiguration() as $extra_configuration) {
            if ($extra_configuration->getServiceName() !== trackerPlugin::SERVICE_SHORTNAME) {
                continue;
            }

            $tracker_shortname = (String)$xml_tracker->item_name;

            if (in_array($tracker_shortname, $extra_configuration->getValue())) {
                $tracker_existing = $this->tracker_factory->getTrackerByShortnameAndProjectId(
                    $tracker_shortname,
                    $project->getID()
                );

                if ($tracker_existing) {
                    $this->fillFieldMappingFromExistingTracker($tracker_existing, $xml_tracker);

                    return $tracker_existing;
                }
            }
        }

        return null;
    }

    /**
     * @param Project $project
     * @param SimpleXMLElement $xml_tracker
     * @return Tracker
     * @throws TrackerFromXmlImportCannotBeUpdatedException
     */
    public function updateFromXML(Project $project, SimpleXMLElement $xml_tracker)
    {
        $tracker_existing = $this->tracker_factory->getTrackerByShortnameAndProjectId((String)$xml_tracker->item_name, $project->getID());

        if (! $tracker_existing) {
            throw new TrackerFromXmlImportCannotBeUpdatedException((String) $xml_tracker->name);
        }

        $this->fillFieldMappingFromExistingTracker($tracker_existing, $xml_tracker);

        return $tracker_existing;
    }

    private function fillFieldMappingFromExistingTracker(Tracker $tracker, SimpleXMLElement $xml_tracker)
    {
        $form_elements_existing   = $this->formelement_factory->getFields($tracker);
        $this->xml_fields_mapping = $this->existing_tracker_field_mapping->getXmlFieldsMapping(
            $xml_tracker,
            $form_elements_existing
        );
    }

    /**
     *
     * @param Project $project
     * @param type $filepath
     *
     * @throws TrackerFromXmlException
     * @return Tracker
     * @throws Tracker_Exception
     */
    public function createFromXMLFile(Project $project, $filepath)
    {
        $xml_security = new XML_Security();
        $tracker_xml = $xml_security->loadFile($filepath);
        if ($tracker_xml !== false) {
            $name        = $tracker_xml->name;
            $description = $tracker_xml->description;
            $item_name   = $tracker_xml->item_name;

            return $this->createFromXML($tracker_xml, $project, $name, $description, $item_name);
        }
    }

    public function getTrackerItemNameFromXMLFile($filepath)
    {
        $xml_security = new XML_Security();
        $tracker_xml = $xml_security->loadFile($filepath);
        if ($tracker_xml !== false) {
            return (string)$tracker_xml->item_name;
        }
    }

    /**
     *
     * @param Project $project
     * @param type $filepath
     * @param type $name
     * @param type $description
     * @param type $item_name
     *
     * @return Tracker
     * @throws TrackerFromXmlException
     * @throws Tracker_Exception
     */
    public function createFromXMLFileWithInfo(Project $project, $filepath, $name, $description, $item_name)
    {
        $xml_security = new XML_Security();
        $tracker_xml  = $xml_security->loadFile($filepath);
        if ($tracker_xml) {
            return $this->createFromXML($tracker_xml, $project, $name, $description, $item_name);
        }
    }

    /**
     * First, creates a new Tracker Object by importing its structure from an XML file,
     * then, imports it into the Database, before verifying the consistency
     *
     * @param SimpleXMLElement $xml_element the location of the imported file
     * @param Project $project the project to create the tracker
     * @param string $name the name of the tracker (label)
     * @param string $description the description of the tracker
     * @param string $itemname the short name of the tracker
     *
     * @return Tracker, or null if error
     * @throws TrackerFromXmlException
     * @throws Tracker_Exception
     */
    public function createFromXML(SimpleXMLElement $xml_element, Project $project, $name, $description, $itemname)
    {
        $tracker = null;
        if ($this->tracker_factory->validMandatoryInfoOnCreate($name, $description, $itemname, $project->getId())) {
            $this->rng_validator->validate(
                $xml_element,
                realpath(dirname(TRACKER_BASE_DIR) . '/www/resources/tracker.rng')
            );
            $feedback_collector = new TrackerXmlImportFeedbackCollector($this->logger);
            $tracker = $this->getInstanceFromXML(
                $xml_element,
                $project,
                $name,
                $description,
                $itemname,
                $feedback_collector
            );
            //Testing consistency of the imported tracker before updating database
            if ($tracker->testImport()) {
                if ($tracker_id = $this->tracker_factory->saveObject($tracker)) {
                    $this->addTrackerProperties($tracker_id, $project, $xml_element);
                    $tracker->setId($tracker_id);
                } else {
                    throw new TrackerFromXmlException(
                        $GLOBALS['Language']->getText('plugin_tracker_admin', 'error_during_creation')
                    );
                }
            } else {
                throw new TrackerFromXmlException('XML file cannot be imported');
            }

            $this->displayWarnings($feedback_collector);
        }

        $this->formelement_factory->clearCaches();
        $this->tracker_factory->clearCaches();

        return $tracker;
    }

    private function addTrackerProperties($tracker_id, Project $project, SimpleXMLElement $xml_element)
    {
        $this->event_manager->processEvent(
            self::ADD_PROPERTY_TO_TRACKER,
            array(
                'xml_element' => $xml_element,
                'tracker_id'  => $tracker_id,
                'project'     => $project,
                'logger'      => $this->logger
            )
        );
    }

    /**
     * Creates a Tracker Object
     *
     * @param SimpleXMLElement  $xml containing the structure of the imported tracker
     * @param Project           $project - the project into which the tracker is imported
     * @param string            $name of the tracker given by the user
     * @param string            $description of the tracker given by the user
     * @param string            $itemname - short_name of the tracker given by the user
     *
     * @return Tracker Object
     * @throws Tracker_Exception
     */
    protected function getInstanceFromXML(SimpleXMLElement $xml, Project $project, $name, $description, $itemname, TrackerXmlImportFeedbackCollector $feedback_collector)
    {
        $xml_tracker_color_name = (string) $xml->color;
        if ($xml_tracker_color_name === '') {
            $tracker_color = TrackerColor::default();
        } else {
            $tracker_color = TrackerColor::fromNotStandardizedName($xml_tracker_color_name);
        }

        // set general settings
        // real id will be set during Database update
        $att = $xml->attributes();
        $row = array(
            'id'                  => 0,
            'name'                => (string)$name,
            'group_id'            => (int)$project->getId(),
            'description'         => (string)$description,
            'item_name'           => (string)$itemname,
            'submit_instructions' => (string)$xml->submit_instructions,
            'browse_instructions' => (string)$xml->browse_instructions,
            'status'              => '',
            'deletion_date'       => '',
            'color'               => $tracker_color->getName()
        );
        $row['allow_copy'] = isset($att['allow_copy']) ?
            (int) $att['allow_copy'] : 0;
        $row['enable_emailgateway'] = isset($att['enable_emailgateway']) ?
            (int) $att['enable_emailgateway'] : 0;
        $row['instantiate_for_new_projects'] = isset($att['instantiate_for_new_projects']) ?
            (int) $att['instantiate_for_new_projects'] : 0;
        $row['log_priority_changes'] = isset($att['log_priority_changes']) ?
            (int) $att['log_priority_changes'] : 0;
        $row['notifications_level'] = $this->getNotificationsLevel($att);

        $tracker = $this->tracker_factory->getInstanceFromRow($row);

        // set canned responses
        if (isset($xml->cannedResponses)) {
            foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
                $tracker->cannedResponses[] = $this->canned_response_factory->getInstanceFromXML($response);
            }
        }

        // set formElements
        foreach ($xml->formElements->formElement as $index => $elem) {
            $form_element = $this->formelement_factory->getInstanceFromXML(
                $tracker,
                $elem,
                $this->xml_fields_mapping,
                $this->user_finder,
                $feedback_collector
            );

            if (! $form_element) {
                continue;
            }

            $tracker->formElements[] = $form_element;
        }

        // set semantics
        if (isset($xml->semantics)) {
            foreach ($xml->semantics->semantic as $xml_semantic) {
                $semantic = $this->semantic_factory->getInstanceFromXML(
                    $xml_semantic,
                    $xml->semantics,
                    $this->xml_fields_mapping,
                    $tracker
                );

                if ($semantic) {
                    $tracker->semantics[] = $semantic;
                }
            }
        }

        /*
         * Legacy compatibility
         *
         * All new Tuleap versions will not export dependencies but rules instead.
         * However, we still want to be able to import old xml files.
         *
         * SimpleXML does not allow for nodes to be moved so have to recursively
         * generate rules from the dependencies data.
         */
        if (isset($xml->dependencies)) {
            $list_rules = null;

            if (! isset($xml->rules)) {
                $list_rules = $xml->addChild('rules')->addChild('list_rules');
            } elseif (! isset($xml->rules->list_rules)) {
                $list_rules = $xml->rules->addChild('list_rules', $xml->dependencies);
            }

            if ($list_rules !== null) {
                foreach ($xml->dependencies->rule as $old_rule) {
                    $source_field_attributes = $old_rule->source_field->attributes();
                    $target_field_attributes = $old_rule->target_field->attributes();
                    $source_value_attributes = $old_rule->source_value->attributes();
                    $target_value_attributes = $old_rule->target_value->attributes();

                    $new_rule = $list_rules->addChild('rule', $old_rule);
                    $new_rule->addChild('source_field')->addAttribute('REF', $source_field_attributes['REF']);
                    $new_rule->addChild('target_field')->addAttribute('REF', $target_field_attributes['REF']);
                    $new_rule->addChild('source_value')->addAttribute('REF', $source_value_attributes['REF']);
                    $new_rule->addChild('target_value')->addAttribute('REF', $target_value_attributes['REF']);
                }
            }
        }

        //set field rules
        if (isset($xml->rules)) {
            $tracker->rules = $this->rule_factory->getInstanceFromXML($xml->rules, $this->xml_fields_mapping, $tracker);
        }

        // set report
        if (isset($xml->reports)) {
            foreach ($xml->reports->report as $report) {
                $tracker->reports[] = $this->report_factory->getInstanceFromXML(
                    $report,
                    $this->xml_fields_mapping,
                    $this->renderers_xml_mapping,
                    $project->getId()
                );
            }
        }

        //set workflow
        if (isset($xml->workflow->field_id)) {
            $tracker->workflow= $this->workflow_factory->getInstanceFromXML(
                $xml->workflow,
                $this->xml_fields_mapping,
                $tracker,
                $project
            );
        } elseif (isset($xml->simple_workflow->field_id)) {
            $tracker->workflow = $this->workflow_factory->getSimpleInstanceFromXML(
                $xml->simple_workflow,
                $this->xml_fields_mapping,
                $tracker,
                $project
            );
        }

        //set webhooks
        if (isset($xml->webhooks)) {
            $tracker->webhooks = $this->webhook_factory->getWebhooksFromXML($xml->webhooks);
        }

        //set permissions
        if (isset($xml->permissions->permission)) {
            $allowed_tracker_perms = array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY);
            $allowed_field_perms   = array('PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_FIELD_SUBMIT');

            foreach ($xml->permissions->permission as $permission) {
                $ugroup_name = (string) $permission['ugroup'];
                $ugroup_id = $this->getUGroupId($project, $ugroup_name);
                if (is_null($ugroup_id)) {
                    $this->logger->error("Custom ugroup '$ugroup_name' does not seem to exist for '{$project->getPublicName()}' project.");
                    continue;
                }
                $type = (string) $permission['type'];

                switch ((string) $permission['scope']) {
                    case 'tracker':
                        //tracker permissions
                        if (!in_array($type, $allowed_tracker_perms)) {
                            $this->logger->error("Can not import permission of type $type for tracker.");
                            break;
                        }
                        $this->logger->debug("Adding '$type' permission to '$ugroup_name' on tracker '{$tracker->getName()}'.");
                        $tracker->setCachePermission($ugroup_id, $type);
                        break;
                    case 'field':
                        //field permissions
                        $REF    = (string) $permission['REF'];
                        if (!in_array($type, $allowed_field_perms)) {
                            $this->logger->error("Can not import permission of type $type for field.");
                            break;
                        }
                        if (!isset($this->xml_fields_mapping[$REF])) {
                            $this->logger->error("Unknow ref to field $REF.");
                            break;
                        }
                        $this->logger->debug("Adding '$type' permission to '$ugroup_name' on field '$REF'.");
                        $this->xml_fields_mapping[$REF]->setCachePermission($ugroup_id, $type);
                        break;
                    default:
                        break;
                }
            }
        }

        return $tracker;
    }

    private function getUGroupId(Project $project, $ugroup_name)
    {
        if (isset($GLOBALS['UGROUPS'][$ugroup_name])) {
            $ugroup_id = $GLOBALS['UGROUPS'][$ugroup_name];
        } else {
            $ugroup = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);
            if (is_null($ugroup)) {
                $ugroup_id = null;
            } else {
                $ugroup_id = $ugroup->getId();
            }
        }
        return $ugroup_id;
    }


    /**
     *
     * @param array $hierarchy
     * @param SimpleXMLElement $xml_tracker
     * @param array $mapper
     * @return array The hierarchy array with new elements added
     */
    protected function buildTrackersHierarchy(array $hierarchy, SimpleXMLElement $xml_tracker, array $mapper)
    {
        $xml_parent_id = $this->getXmlTrackerAttribute($xml_tracker, 'parent_id');

        if ($xml_parent_id != self::XML_PARENT_ID_EMPTY) {
            $parent_id  = $mapper[$xml_parent_id];
            $tracker_id = $mapper[$this->getXmlTrackerAttribute($xml_tracker, 'id')];

            if (! isset($hierarchy[$parent_id])) {
                $hierarchy[$parent_id] = array();
            }

            array_push($hierarchy[$parent_id], $tracker_id);
        }

        return $hierarchy;
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
     * @param $att
     *
     * @return int
     */
    protected function getNotificationsLevel($att)
    {
        $deprecated_stop_notification = isset($att['stop_notification'])
            ? (int) $att['stop_notification']
            : self::DEFAULT_NOTIFICATIONS_LEVEL;

        $notifications_level = isset($att['notifications_level'])
            ? (int) $att['notifications_level']
            : $deprecated_stop_notification;

        return $notifications_level;
    }

    private function displayWarnings(TrackerXmlImportFeedbackCollector $feedback_collector)
    {
        if (empty($feedback_collector->getWarnings())) {
            return;
        }

        $feedback_collector->displayWarnings($this->logger);
    }
}
