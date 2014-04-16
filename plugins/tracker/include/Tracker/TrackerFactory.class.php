<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class TrackerFactory {

    /** @var array of Tracker */
    protected $trackers;

    /** @var Tracker_HierarchyFactory */
    private $hierarchy_factory;

    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {
        $this->trackers = array();
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return TrackerFactory
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     *
     * @param TrackerFactory $factory
     */
    public static function setInstance(TrackerFactory $factory) {
        self::$_instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance() {
        self::$_instance = null;
    }

    /**
     * @param int $id the id of the tracker to retrieve
     * @return Tracker identified by id (null if not found)
     */
    public function getTrackerById($tracker_id) {
        if (!isset($this->trackers[$tracker_id])) {
            $this->trackers[$tracker_id] = null;
            if ($row = $this->getDao()->searchById($tracker_id)->getRow()) {
                $this->getCachedInstanceFromRow($row);
            }
        }
        return $this->trackers[$tracker_id];
    }

    /**
     * @param int $group_id the project id the trackers to retrieve belong to
     *
     * @return Array of Tracker
     */
    public function getTrackersByGroupId($group_id) {
        $trackers = array();
        foreach($this->getDao()->searchByGroupId($group_id) as $row) {
            $tracker_id = $row['id'];
            $trackers[$tracker_id] = $this->getCachedInstanceFromRow($row);
        }
        return $trackers;
    }

    /**
     * @return array of Tracker
     */
    public function getTrackersByGroupIdUserCanView($group_id, PFUser $user) {
        $trackers = array();
        foreach($this->getDao()->searchByGroupId($group_id) as $row) {
            $tracker_id = $row['id'];
            $tracker    = $this->getCachedInstanceFromRow($row);
            if($tracker->userCanView($user)) {
                $trackers[$tracker_id] = $tracker;
            }
        }
        return $trackers;
    }

    /**
     * @param Tracker $tracker
     *
     * @return Children trackers of the given tracker.
     */
    public function getPossibleChildren($tracker) {
        $project_id = $tracker->getGroupId();
        $trackers   = $this->getTrackersByGroupId($project_id);

        unset($trackers[$tracker->getId()]);
        return $trackers;
    }

    protected $dao;

    /**
     * @return TrackerDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new TrackerDao();
        }
        return $this->dao;
    }

    /**
     * @param array $row Raw data (typically from the db) of the tracker
     *
     * @return Tracker
     */
    private function getCachedInstanceFromRow($row) {
        $tracker_id = $row['id'];
        if (!isset($this->trackers[$tracker_id])) {
            $this->trackers[$tracker_id] = $this->getInstanceFromRow($row);
        }
        return $this->trackers[$tracker_id];
    }

    /**
     * /!\ Only for tests
     */
    public function setCachedInstances($trackers) {
        $this->trackers = $trackers;
    }

    /**
     * @param array the row identifing a tracker
     * @return Tracker
     */
    public function getInstanceFromRow($row) {
        return new Tracker(
                    $row['id'],
                    $row['group_id'],
                    $row['name'],
                    $row['description'],
                    $row['item_name'],
                    $row['allow_copy'],
                    $row['submit_instructions'],
                    $row['browse_instructions'],
                    $row['status'],
                    $row['deletion_date'],
                    $row['instantiate_for_new_projects'],
                    $row['stop_notification'],
                    $row['color']
        );
    }

    /**
     * Creates a Tracker Object
     *
     * @param SimpleXMLElement $xml containing the structure of the imported tracker
     * @param int $groupId - id of the project into which the tracker is imported
     * @param string $name of the tracker given by the user
     * @param string $description of the tracker given by the user
     * @param string $itemnate - short_name of the tracker given by the user
     *
     * @return Tracker Object
     */
    public function getInstanceFromXML($xml, $groupId, $name, $description, $itemname) {
        // set general settings
        // real id will be set during Database update
        $att = $xml->attributes();
        $row = array(
            'id'                  => 0,
            'name'                => $name,
            'group_id'            => $groupId,
            'description'         => $description,
            'item_name'           => $itemname,
            'submit_instructions' => (string)$xml->submit_instructions,
            'browse_instructions' => (string)$xml->browse_instructions,
            'status'              => '',
            'deletion_date'       => '',
            'color'               => (string)$xml->color
        );
        $row['allow_copy'] = isset($att['allow_copy']) ?
                (int) $att['allow_copy'] : 0;
        $row['instantiate_for_new_projects'] = isset($att['instantiate_for_new_projects']) ?
                (int) $att['instantiate_for_new_projects'] : 0;
        $row['stop_notification'] = isset($att['stop_notification']) ?
                (int) $att['stop_notification'] : 0;

        $tracker = $this->getInstanceFromRow($row);
        // set canned responses
        foreach ($xml->cannedResponses->cannedResponse as $index => $response) {
            $tracker->cannedResponses[] = $this->getCannedResponseFactory()->getInstanceFromXML($response);
        }
        // set formElements
        // association between ids in XML and php objects
        $xmlMapping = array();
        foreach ($xml->formElements->formElement as $index => $elem) {
            $tracker->formElements[] = $this->getFormElementFactory()->getInstanceFromXML($tracker, $elem, $xmlMapping);
        }

        // set semantics
        if (isset($xml->semantics)) {
            foreach ($xml->semantics->semantic as $xml_semantic) {
                $semantic = $this->getSemanticFactory()->getInstanceFromXML($xml_semantic, $xmlMapping, $tracker);
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
             
            if(! isset($xml->rules)) {
                $list_rules = $xml->addChild('rules')->addChild('list_rules');    
            } elseif (! isset($xml->rules->list_rules)) {
                $list_rules = $xml->rules->addChild('list_rules', $xml->dependencies);    
            } 
             
            if($list_rules !== null) {
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
            $tracker->rules = $this->getRuleFactory()->getInstanceFromXML($xml->rules, $xmlMapping, $tracker);
        }

        // set report
        if (isset($xml->reports)) {
            foreach ($xml->reports->report as $report) {
                $tracker->reports[] = $this->getReportFactory()->getInstanceFromXML($report, $xmlMapping, $groupId);
            }
        }

        //set workflow
        if (isset($xml->workflow->field_id)) {
            $tracker->workflow= $this->getWorkflowFactory()->getInstanceFromXML($xml->workflow, $xmlMapping, $tracker);
        }

        //set permissions
        if (isset($xml->permissions->permission)) {
            $allowed_tracker_perms = array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_FULL, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_SUBMITTER_ONLY);
            $allowed_field_perms = array('PLUGIN_TRACKER_FIELD_READ', 'PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_FIELD_SUBMIT');
            foreach ($xml->permissions->permission as $permission) {
                switch ((string) $permission['scope']) {
                    case 'tracker':
                        //tracker permissions
                        $ugroup = (string) $permission['ugroup'];
                        $type   = (string) $permission['type'];
                        if (isset($GLOBALS['UGROUPS'][$ugroup]) && in_array($type, $allowed_tracker_perms)) {
                            $tracker->setCachePermission($GLOBALS['UGROUPS'][$ugroup], $type);
                        }
                        break;
                    case 'field':
                        //field permissions
                        $ugroup = (string) $permission['ugroup'];
                        $REF    = (string) $permission['REF'];
                        $type   = (string) $permission['type'];
                        if (isset($xmlMapping[$REF]) && isset($GLOBALS['UGROUPS'][$ugroup]) && in_array($type, $allowed_field_perms)) {
                            $xmlMapping[$REF]->setCachePermission($GLOBALS['UGROUPS'][$ugroup], $type);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $tracker;
    }

    /**
     * @return Tracker_CannedResponseFactory
     */
    protected function getCannedResponseFactory() {
        return Tracker_CannedResponseFactory::instance();
    }

    /**
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * @return Tracker_SemanticFactory
     */
    protected function getSemanticFactory() {
        return Tracker_SemanticFactory::instance();
    }

    /**
     * @return Tracker_RuleFactory
     */
    protected function getRuleFactory() {
        return Tracker_RuleFactory::instance();
    }

    /**
     * @return Tracker_ReportFactory
     */
    protected function getReportFactory() {
        return Tracker_ReportFactory::instance();
    }

    /**
     * @return WorkflowFactory
     */
    protected function getWorkflowFactory() {
        return WorkflowFactory::instance();
    }

    /**
     * @return ReferenceManager
     */
    protected function getReferenceManager() {
        return ReferenceManager::instance();
    }

    /**
     * @return ProjectManager
     */
    protected function getProjectManager() {
        return ProjectManager::instance();
    }

    /**
     * Mark the tracker as deleted
     */
    public function markAsDeleted($tracker_id) {
        return $this->getDao()->markAsDeleted($tracker_id);
    }

    /**
     * Check if the name of the tracker is already used in the project
     * @param string $name the name of the tracker we are looking for
     * @param int $group_id th ID of the group
     * @return boolean
     */
    public function isNameExists($name, $group_id) {
        $tracker_dao = $this->getDao();
        $dar = $tracker_dao->searchByGroupId($group_id);
        while ($row = $dar->getRow()) {
            if ($name == $row['name']) {
                return true;
            }
        }
        return false;
    }

   /**
    * Check if the shortname of the tracker is already used in the project
    * @param string $shortname the shortname of the tracker we are looking for
    * @param int $group_id the ID of the group
    * @return boolean
    */
    public function isShortNameExists($shortname, $group_id) {
        $tracker_dao = $this->getDao();
        return $tracker_dao->isShortNameExists($shortname, $group_id);
    }

    /**
     * Valid the name, description and itemname on creation.
     * Add feedback if error.
     *
     * @param string $name        the name of the new tracker
     * @param string $description the description of the new tracker
     * @param string $itemname    the itemname of the new tracker
     * @param int    $group_id    the id of the group of the new tracker
     *
     * @return bool true if all valid
     */
    protected function validMandatoryInfoOnCreate($name, $description, $itemname, $group_id) {
        if (!$name || !$description || !$itemname || trim($name) == "" || trim($description) == "" || trim($itemname) == ""  ) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','name_requ'));
            return false;
        }

        // Necessary test to avoid issues when exporting the tracker to a DB (e.g. '-' not supported as table name)
        if (!eregi("^[a-zA-Z0-9_]+$",$itemname)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','invalid_shortname',$itemname));
            return false;
        }

        if($this->isNameExists($name, $group_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','name_already_exists',$itemname));
            return false;
        }

        if($this->isShortNameExists($itemname, $group_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','shortname_already_exists',$itemname));
            return false;
        }

        $reference_manager = $this->getReferenceManager();
        if($reference_manager->_isKeywordExists($itemname, $group_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','shortname_already_exists',$itemname));
            return false;
        }



        return true;
    }

    /**
     * create - use this to create a new Tracker in the database.
     *
     * @param Project $project_id          the group id of the new tracker
     * @param int     $project_id_template the template group id (used for the copy)
     * @param int     $id_template         the template tracker id
     * @param string  $name                the name of the new tracker
     * @param string  $description         the description of the new tracker
     * @param string  $itemname            the itemname of the new tracker
     * @param Array   $ugroup_mapping the ugroup mapping
     *
     * @return mixed array(Tracker object, field_mapping array) or false on failure.
     */
    function create($project_id, $project_id_template, $id_template, $name, $description, $itemname, $ugroup_mapping = false) {

        if ($this->validMandatoryInfoOnCreate($name, $description, $itemname, $project_id)) {

            // Get the template tracker
            $template_tracker = $this->getTrackerById($id_template);
            if (!$template_tracker) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','invalid_tracker_tmpl'));
                return false;
            }

            $template_group = $template_tracker->getProject();
            if (!$template_group || !is_object($template_group) || $template_group->isError()) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','invalid_templ'));
                return false;
            }
            $project_id_template = $template_group->getId();

            //Ask to dao to duplicate the tracker
            if ($id = $this->getDao()->duplicate($id_template, $project_id, $name, $description, $itemname)) {

                // Duplicate Form Elements
                $field_mapping = Tracker_FormElementFactory::instance()->duplicate($id_template, $id, $ugroup_mapping);

                if ($ugroup_mapping) {
                    $duplicate_type = PermissionsDao::DUPLICATE_NEW_PROJECT;
                } else if ($project_id == $project_id_template) {
                     $duplicate_type = PermissionsDao::DUPLICATE_SAME_PROJECT;
                } else {
                    $ugroup_manager = new UGroupManager();
                    $builder = new Tracker_UgroupMappingBuilder(new Tracker_UgroupPermissionsGoldenRetriever(new Tracker_PermissionsDao(), $ugroup_manager), $ugroup_manager);
                    $ugroup_mapping = $builder->getMapping($template_tracker, ProjectManager::instance()->getProject($project_id));
                    $duplicate_type = PermissionsDao::DUPLICATE_OTHER_PROJECT;
                }

                // Duplicate workflow
                foreach ($field_mapping as $mapping) {
                    if ($mapping['workflow']) {
                        WorkflowFactory::instance()->duplicate($id_template, $id, $mapping['from'], $mapping['to'], $mapping['values'], $field_mapping, $ugroup_mapping, $duplicate_type);
                    }
                }
                // Duplicate Reports
                Tracker_ReportFactory::instance()->duplicate($id_template, $id, $field_mapping);

                // Duplicate Semantics
                Tracker_SemanticFactory::instance()->duplicate($id_template, $id, $field_mapping);

                // Duplicate Canned Responses
                Tracker_CannedResponseFactory::instance()->duplicate($id_template, $id);
                //Duplicate field dependencies
                $this->getRuleFactory()->duplicate($id_template, $id, $field_mapping);
                $tracker = $this->getTrackerById($id);

                // Process event that tracker is created
                $em =& EventManager::instance();
                $pref_params = array('atid_source' => $id_template,
                        'atid_dest'   => $id);
                $em->processEvent('Tracker_created', $pref_params);
                //Duplicate Permissions
                $this->duplicatePermissions($id_template, $id, $ugroup_mapping, $field_mapping, $duplicate_type);


                $this->postCreateActions($tracker);

                return array('tracker' => $tracker, 'field_mapping' => $field_mapping);
            }
        }
        return false;
    }

   /**
    * Duplicat the permissions of a tracker
    *
    * @param int $id_template the id of the duplicated tracker
    * @param int $id          the id of the new tracker
    * @param array $ugroup_mapping
    * @param array $field_mapping
    * @param bool $duplicate_type
    *
    * @return bool
    */
    public function duplicatePermissions($id_template, $id, $ugroup_mapping, $field_mapping, $duplicate_type) {
        $pm = PermissionsManager::instance();
        $permission_type_tracker = array(Tracker::PERMISSION_ADMIN, Tracker::PERMISSION_SUBMITTER, Tracker::PERMISSION_SUBMITTER_ONLY, Tracker::PERMISSION_ASSIGNEE, Tracker::PERMISSION_FULL, Tracker::PERMISSION_NONE);
        //Duplicate tracker permissions
        $pm->duplicatePermissions($id_template, $id, $permission_type_tracker, $ugroup_mapping, $duplicate_type);

        $permission_type_field = array('PLUGIN_TRACKER_FIELD_SUBMIT','PLUGIN_TRACKER_FIELD_READ','PLUGIN_TRACKER_FIELD_UPDATE', 'PLUGIN_TRACKER_NONE');
        //Duplicate fields permissions
        foreach ($field_mapping as $f) {
            $from = $f['from'];
            $to = $f['to'];
            $pm->duplicatePermissions($from, $to, $permission_type_field, $ugroup_mapping, $duplicate_type);
        }
    }

    /**
     * Do all stuff which have to be done after a tracker creation, like reference creation for example
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    protected function postCreateActions(Tracker $tracker) {
        // Create corresponding reference
        $ref = new Reference(
                // no ID yet
                0,
                // keyword
                strtolower($tracker->getItemName()),
                // description
                $GLOBALS['Language']->getText('project_reference','reference_art_desc_key') .' - '. $tracker->getName(),
                // link
                TRACKER_BASE_URL.'/?aid=$1&group_id=$group_id',
                // scope is 'project'
                'P',
                // service short name
                'plugin_tracker',
                // nature
                Tracker_Artifact::REFERENCE_NATURE,
                // is_used
                '1',
                // project id
                $tracker->getGroupId()
        );
        // Force reference creation because default trackers use reserved keywords
        $this->getReferenceManager()->createReference($ref, true);
    }

    /**
     * Duplicate all trackers from a project to another one
     *
     * Duplicate among others:
     * - the trackers definition
     * - the hierarchy
     * - the shared fields
     * - etc.
     * 
     * @param int $from_project_id 
     * @param int $to_project_id
     * @param array $ugroup_mapping the ugroup mapping
     *
     */
    public function duplicate($from_project_id, $to_project_id, $ugroup_mapping) {
        $tracker_mapping        = array();
        $field_mapping          = array();
        $trackers_from_template = array();

        foreach($this->getTrackersByGroupId($from_project_id) as $tracker) {
            if ($tracker->mustBeInstantiatedForNewProjects()) {
                $trackers_from_template[] = $tracker;
                list($tracker_mapping, $field_mapping) = $this->duplicateTracker(
                        $tracker_mapping, 
                        $field_mapping, 
                        $tracker, 
                        $from_project_id, 
                        $to_project_id, 
                        $ugroup_mapping
                        );
                /*
                 * @todo
                 * Unless there is some odd dependency on the last tracker meeting
                 * the requirement of the if() condition then there should be a break here. 
                 */
            }
        }

        /*
         * @todo
         * $tracker_mapping has been defined as an array. Surely this should be
         * if(! empty($tracker_mapping))
         */
        if ($tracker_mapping) {
            $hierarchy_factory = $this->getHierarchyFactory();
            $hierarchy_factory->duplicate($tracker_mapping);

            $trigger_rules_manager = $this->getTriggerRulesManager();
            $trigger_rules_manager->duplicate($trackers_from_template, $field_mapping);

        }
        $shared_factory = $this->getFormElementFactory();
        $shared_factory->fixOriginalFieldIdsAfterDuplication($to_project_id, $from_project_id, $field_mapping);

        EventManager::instance()->processEvent(TRACKER_EVENT_TRACKERS_DUPLICATED, array(
            'tracker_mapping' => $tracker_mapping,
            'field_mapping'   => $field_mapping,
            'group_id'        => $to_project_id
        ));
    }

    /**
     * @return Tracker_Workflow_Trigger_RulesManager
     */
    protected function getTriggerRulesManager() {
        $trigger_rule_dao        = new Tracker_Workflow_Trigger_RulesDao();
        $workflow_backend_logger = new WorkflowBackendLogger(new BackendLogger());
        $rules_processor         = new Tracker_Workflow_Trigger_RulesProcessor(
            new Tracker_Workflow_WorkflowUser(),
            $workflow_backend_logger
        );

        return new Tracker_Workflow_Trigger_RulesManager(
            $trigger_rule_dao,
            $this->getFormElementFactory(),
            $rules_processor,
            $workflow_backend_logger
        );
    }

    /**
     * 
     * @param array $tracker_mapping
     * @param array $field_mapping
     * @param Tracker $tracker
     * @param int $from_project_id
     * @param int $to_project_id
     * @param array $ugroup_mapping the ugroup mapping
     * @return type
     */
    private function duplicateTracker($tracker_mapping, $field_mapping, $tracker, $from_project_id, $to_project_id, $ugroup_mapping) {
        $tracker_and_field_mapping = $this->create($to_project_id,
                $from_project_id,
                $tracker->getId(),
                $tracker->getName(),
                $tracker->getDescription(),
                $tracker->getItemName(),
                $ugroup_mapping);

        if ($tracker_and_field_mapping) {
            $tracker_mapping[$tracker->getId()] = $tracker_and_field_mapping['tracker']->getId();
            $field_mapping = array_merge($field_mapping, $tracker_and_field_mapping['field_mapping']);
        } else {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin','tracker_not_duplicated', array($tracker->getName())));
        }

        return array($tracker_mapping, $field_mapping);
    }

    /**
     * /!\ Only for tests
     */
    public function setHierarchyFactory(Tracker_HierarchyFactory $hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
    }

    /**
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory() {
        if (!$this->hierarchy_factory) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }

    /**
     * @return Hierarchy
     */
    public function getHierarchy(array $tracker_ids) {
        return $this->getHierarchyFactory()->getHierarchy($tracker_ids);
    }

    /**
     * Create a dom document based on a SimpleXMLElement
     *
     * @param SimpleXMLElement $xml_element
     *
     * @return \DOMDocument
     */
    private function simpleXmlElementToDomDocument(SimpleXMLElement $xml_element) {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom_element = $dom->importNode(dom_import_simplexml($xml_element), true);
        $dom->appendChild($dom_element);
        return $dom;
    }

    /**
     * First, creates a new Tracker Object by importing its structure from an XML file,
     * then, imports it into the Database, before verifying the consistency
     *
     * @param string         $xml_element        the location of the imported file
     * @param int            $groupId        the Id of the project to create the tracker
     * @param string         $name           the name of the tracker (label)
     * @param string         $description    the description of the tracker
     * @param string         $itemname       the short name of the tracker
     * @param TrackerManager $trackermanager an instance of TrackerManager
     *
     * @return the new Tracker, or null if error
     */
    public function createFromXML(SimpleXMLElement $xml_element, $groupId, $name, $description, $itemname, $trackermanager = null) {
        $tracker = null;
        if ($this->validMandatoryInfoOnCreate($name, $description, $itemname, $groupId)) {
            // XML validation before creating a new tracker
            $dom = $this->simpleXmlElementToDomDocument($xml_element);
            $rng = realpath(dirname(__FILE__).'/../../www/resources/tracker.rng');
            if(!@$dom->relaxNGValidate($rng)) { //hide warning since we will extract the errors below
                //try to be more verbose for the end user (RelaxNG notices are hidden)
                $hp       = Codendi_HTMLPurifier::instance();
                $indent   = $GLOBALS['codendi_utils_prefix'] .'/xml/indent.xsl';
                $jing     = $GLOBALS['codendi_utils_prefix'] .'/xml/jing.jar';
                $temp     = tempnam($GLOBALS['tmp_dir'], 'xml');
                $xml_file = tempnam($GLOBALS['tmp_dir'], 'xml_src_');
                file_put_contents($xml_file, $dom->saveXML());
                $cmd_indent = "xsltproc -o $temp $indent $xml_file";
                `$cmd_indent`;

                $output = array();
                $cmd_valid = "java -jar $jing $rng $temp";
                exec($cmd_valid, $output);
                $errors = array();
                if ( $trackermanager ) {

                    $project = ProjectManager::instance()->getProject($groupId);
                    $breadcrumbs = array(
                            array(
                                    'title' => 'Create a new tracker',
                                    'url'   => TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&amp;func=create'
                            )
                    );
                    $toolbar = array();
                    $params  = array();

                    $trackermanager->displayHeader($project, 'Trackers', $breadcrumbs, $toolbar, $params);
                    //var_dump($cmd_indent, $cmd_valid);
                    echo '<h2>XML file doesnt have correct format</h2>';

                    foreach($output as $o) {
                        $matches = array();
                        preg_match('/:(\d+):(\d+):([^:]+):(.*)/', $o, $matches);
                        //1 line
                        //2 column
                        //3 type
                        //4 message
                        $errors[$matches[1]][$matches[2]][] = array(trim($matches[3]) => trim($matches[4]));
                        echo '<a href="#line_'. $matches[1] .'">'. $matches[3] .': '. $matches[4] .'</a><br />';
                    }
                    $clear = $GLOBALS['HTML']->getimage('clear.png', array('width' => 24, 'height' => 1));
                    $icons = array(
                            'error' => $GLOBALS['HTML']->getimage('ic/error.png', array('style' => 'vertical-align:middle')),
                    );
                    $styles = array(
                            'error' => 'color:red; font-weight:bold;',
                    );
                    echo '<pre>';
                    foreach(file($temp) as $number => $line) {
                        echo '<div id="line_'. ($number + 1) .'">';
                        echo  '<span style="color:gray;">'. sprintf('%4d', $number+1). '</span>'. $clear . $hp->purify($line, CODENDI_PURIFIER_CONVERT_HTML) ;
                        if (isset($errors[$number + 1])) {
                            foreach($errors[$number + 1] as $c => $e) {
                                echo '<div>'. sprintf('%3s', ''). $clear . sprintf('%'. ($c-1) .'s', '') .'<span style="color:blue; font-weight:bold;">^</span></div>';
                                foreach($e as $error) {
                                    foreach($error as $type => $message) {
                                        $style = isset($styles['error']) ? $styles['error'] : '';
                                        echo '<div style="'. $style .'">';
                                        if (isset($icons[$type])) {
                                            echo $icons[$type];
                                        } else {
                                            echo $clear;
                                        }
                                        echo sprintf('%3s', '').sprintf('%'. ($c-1) .'s', '') .$message;
                                        echo '</div>';
                                    }
                                }
                            }
                        }
                        echo '</div>';
                    }
                    echo '</pre>';
                    unlink($temp);
                    $trackermanager->displayFooter($project);
                    exit;
                } else {
                    unlink($temp);
                    echo PHP_EOL;
                    echo implode(PHP_EOL, $output);
                    echo PHP_EOL;
                }

            } else {
                $tracker = $this->getInstanceFromXML($xml_element, $groupId, $name, $description, $itemname);
                //Testing consistency of the imported tracker before updating database
                if ($tracker->testImport()) {
                    if ($tracker_id = $this->saveObject($tracker)) {
                        $tracker->setId($tracker_id);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'error_during_creation'));
                        $tracker = null;
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'XML file cannot be imported');
                    $tracker = null;
                }
            }
        }

        return $tracker;
    }

    /**
     * Saves the default permission of a tracker in the db
     *
     * @param int $tracker_id the id of the tracker
     * @return bool
     */
    public function saveTrackerDefaultPermission($tracker_id) {
        $pm = PermissionsManager::instance();
        if(!$pm->addPermission(Tracker::PERMISSION_FULL, $tracker_id, ProjectUGroup::ANONYMOUS)) {
            return false;
        }
        return true;
    }

    /**
     * Saves a Tracker object into the DataBase
     *
     * @param Tracker $tracker object to save
     * @return int id of the newly created tracker
     */
    public function saveObject($tracker) {
        // create tracker
        $this->getDao()->startTransaction();
        $tracker_id = $this->getDao()->create(
                $tracker->group_id,
                $tracker->name,
                $tracker->description,
                $tracker->item_name,
                $tracker->allow_copy,
                $tracker->submit_instructions,
                $tracker->browse_instructions,
                '',
                '',
                $tracker->instantiate_for_new_projects,
                $tracker->stop_notification,
                $tracker->color
        );
        if ($tracker_id) {
            $trackerDB = $this->getTrackerById($tracker_id);
            //create cannedResponses
            $response_factory = $tracker->getCannedResponseFactory();
            foreach ($tracker->cannedResponses as $response) {
                $response_factory->saveObject($tracker_id, $response);
            }
            //create formElements
            foreach ($tracker->formElements as $formElement) {
                // these fields have no parent
                Tracker_FormElementFactory::instance()->saveObject($trackerDB, $formElement, 0);
            }
            //create report
            foreach ($tracker->reports as $report) {
                Tracker_ReportFactory::instance()->saveObject($tracker_id, $report);
            }
            //create semantics
            if (isset($tracker->semantics)) {
                foreach ($tracker->semantics as $semantic) {
                    Tracker_SemanticFactory::instance()->saveObject($semantic, $trackerDB);
                }
            }
            //create rules
            if (isset($tracker->rules)) {
                $this->getRuleFactory()->saveObject($tracker->rules, $trackerDB);
            }
            //create workflow
            if (isset($tracker->workflow)) {
                WorkflowFactory::instance()->saveObject($tracker->workflow, $trackerDB);
            }

            //tracker permissions
            if ($tracker->permissionsAreCached()) {
                $pm = PermissionsManager::instance();
                foreach ($tracker->getPermissionsByUgroupId() as $ugroup => $permissions) {
                    foreach ($permissions as $permission) {
                        $pm->addPermission($permission, $tracker_id, $ugroup);
                    }
                }
            } else {
                $this->saveTrackerDefaultPermission($tracker_id);
            }

            $this->postCreateActions($trackerDB);
        }
        $this->getDao()->commit();
        return $tracker_id;
    }

    /**
     * Create a tracker v5 from a tracker v3
     *
     * @param int            $atid           the id of the tracker v3
     * @param Project        $project        the Id of the project to create the tracker
     * @param string         $name           the name of the tracker (label)
     * @param string         $description    the description of the tracker
     * @param string         $itemname       the short name of the tracker
     *
     * @return Tracker
     */
    public function createFromTV3($atid, Project $project, $name, $description, $itemname) {
        require_once 'common/tracker/ArtifactType.class.php';
        $tv3 = new ArtifactType($project, $atid);
        if (!$tv3 || !is_object($tv3)) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('tracker_index','not_create_at'));
        }
        if ($tv3->isError()) {
            exit_error($GLOBALS['Language']->getText('global','error'),$tv3->getErrorMessage());
        }
        // Check if this tracker is valid (not deleted)
        if ( !$tv3->isValid() ) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('tracker_add','invalid'));
        }
        //Check if the user can view the artifact
        if (!$tv3->userCanView()) {
            exit_permission_denied();
        }

        $tracker = null;
        if ($this->validMandatoryInfoOnCreate($name, $description, $itemname, $project->getId())) {
            $migration_v3 = new Tracker_Migration_V3($this);
            $tracker = $migration_v3->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
            $this->postCreateActions($tracker);
        }
        return $tracker;
    }
}
?>
