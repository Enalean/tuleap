<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Project.class.php';
require_once 'Project_InvalidShortName_Exception.class.php';
require_once 'Project_InvalidFullName_Exception.class.php';
require_once 'Project_Creation_Exception.class.php';
require_once 'common/valid/Rule.class.php';
require_once 'service.php';
require_once 'www/forum/forum_utils.php';
require_once 'www/admin/admin_utils.php';
require_once 'common/tracker/ArtifactType.class.php';
require_once 'common/tracker/ArtifactTypeFactory.class.php';
require_once 'common/tracker/ArtifactFieldFactory.class.php';
require_once 'common/tracker/ArtifactField.class.php';
require_once 'common/tracker/ArtifactFieldSetFactory.class.php';
require_once 'common/tracker/ArtifactFieldSet.class.php';
require_once 'common/tracker/ArtifactReport.class.php';
require_once 'common/tracker/ArtifactReportFactory.class.php';
require_once 'common/reference/ReferenceManager.class.php';
require_once 'trove.php';
require_once 'common/event/EventManager.class.php';
require_once 'common/wiki/lib/WikiCloner.class.php';

define('PROJECT_APPROVAL_BY_ADMIN', 'P');
define('PROJECT_APPROVAL_AUTO',     'A');

use Tuleap\project\Event\ProjectRegistrationActivateService;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\ProjectRegistrationDisabledException;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Service\ServiceCreator;

/**
 * Manage creation of a new project in the forge.
 *
 * For now, mainly a wrapper for createProject method
 */
class ProjectCreator {

    /**
     * When a project is created, ask plugins if they replace the usage of legacy core services
     *
     * Parameters:
     *  - template              => (input) Project
     *  - project_creation_data => (output) array
     *  - use_legacy_services   => (output) array
     */
    const PROJECT_CREATION_REMOVE_LEGACY_SERVICES = 'project_creation_remove_legacy_services';

    /**
     * Waiting for "private const" in PHP 7.1 https://wiki.php.net/rfc/class_const_visibility
     */
    private static $TYPE_PROJECT  = 1;
    private static $TYPE_TEMPLATE = 2;
    private static $TYPE_TEST     = 3;

    /**
     * @var UgroupDuplicator
     */
    private $ugroup_duplicator;

    /**
     * @var bool true to bypass manual activation
     */
    private $force_activation;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Rule_ProjectName
     */
    private $ruleShortName;

    /**
     * @var Rule_ProjectFullName
     */
    private $ruleFullName;

    private $send_notifications;

    /**
     * @var FRSPermissionCreator
     */
    private $frs_permissions_creator;
    /**
     * @var ProjectDashboardDuplicator
     */
    private $dashboard_duplicator;
    /**
     * @var ServiceCreator
     */
    private $service_creator;
    /**
     * @var LabelDao
     */
    private $label_dao;

    public function __construct(
        ProjectManager $projectManager,
        ReferenceManager $reference_manager,
        UserManager $user_manager,
        UgroupDuplicator $ugroup_duplicator,
        $send_notifications,
        FRSPermissionCreator $frs_permissions_creator,
        ProjectDashboardDuplicator $dashboard_duplicator,
        ServiceCreator $service_creator,
        LabelDao $label_dao,
        $force_activation = false
    ) {
        $this->send_notifications      = $send_notifications;
        $this->force_activation        = $force_activation;
        $this->reference_manager       = $reference_manager;
        $this->user_manager            = $user_manager;
        $this->ruleShortName           = new Rule_ProjectName();
        $this->ruleFullName            = new Rule_ProjectFullName();
        $this->projectManager          = $projectManager;
        $this->frs_permissions_creator = $frs_permissions_creator;
        $this->ugroup_duplicator       = $ugroup_duplicator;
        $this->dashboard_duplicator    = $dashboard_duplicator;
        $this->service_creator         = $service_creator;
        $this->label_dao               = $label_dao;
    }

    /**
     * Build a new project
     *
     * @param ProjectCreationData $data project data
     * @return Project created
     */
    public function build(ProjectCreationData $data)
    {
        if (! \ForgeConfig::get('sys_use_project_registration') && ! $this->user_manager->getCurrentUser()->isSuperUser()) {
            throw new ProjectRegistrationDisabledException();
        }

        if (!$this->ruleShortName->isValid($data->getUnixName())) {
            throw new Project_InvalidShortName_Exception($this->ruleShortName->getErrorMessage());
        }

        if (!$this->ruleFullName->isValid($data->getFullName())) {
            throw new Project_InvalidFullName_Exception($this->ruleFullName->getErrorMessage());
        }

        $id = $this->createProject($data);
        if ($id) {
            return $this->projectManager->getProject($id);
        }
        throw new Project_Creation_Exception();
    }

    /**
     * Create a new project
     *
     * $data['project']['form_unix_name']
     * $data['project']['form_full_name']
     * $data['project']['form_short_description']
     * $data['project']['built_from_template']
     * $data['project']['is_test']
     * $data['project']['is_public']
     * $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
     * foreach($data['project']['trove'] as $root => $values);
     * $data['project']['services'][$arr['service_id']]['is_used'];
     * $data['project']['services'][$arr['service_id']]['server_id'];
     *
     * @param String $shortName, the unix name
     * @param String $publicName, the full name
     * @param Array $data
     *
     * @return Project
     */
    public function create($shortName, $publicName, array $data) {
        $creationData = ProjectCreationData::buildFromFormArray($data);

        $creationData->setUnixName($shortName);
        $creationData->setFullName($publicName);
        return $this->build($creationData);
    }

    private function fakeGroupIdIntoHTTPParams($group_id){
        $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
        $request = HTTPRequest::instance();
        $request->params['group_id'] = $_REQUEST['group_id'];
    }

    /**
     * createProject
     *
     * Create a new project
     *
     * Insert in group table
     * Insert group_desc_value, trove_group_link
     * Create filemodule in DB
     * Assign an admin user
     * Copy from template:
     * - activate the same services (using the ame server id and options)
     * - send message to the project requested (pepend on template values)
     * - create forums with the same name and public status
     * - copy CVS properties
     * - copy SVN settings and start .SVNAccessFile hisr=tiry
     * - add system references withut services
     * - copy ugroups and save mapping for further import
     * - copy FRS packages with permissions
     * - copy trackers
     * - copy wiki
     * - copy layout summary page
     * - Add the template as a project reference
     * - Copy Truncated email option
     * - Raise an event for plugin configuration
     *
     * @param  data ProjectCreationData
     */
    protected function createProject(ProjectCreationData $data) {
        $admin_user = $this->user_manager->getCurrentUser();

        $group_id = $this->createGroupEntry($data);
        if ($group_id === false) {
            return;
        }

        $this->setCategories($data, $group_id);
        $this->initFileModule($group_id);
        $this->setProjectAdmin($group_id, $admin_user);

        // Instanciate all services from the project template that are 'active'
        $group = $this->projectManager->getProject($group_id);
        if (!$group || !is_object($group)) {
            exit_no_group();
        }

        $this->fakeGroupIdIntoHTTPParams($group_id);

        $template_id    = $group->getTemplate();
        $template_group = $this->projectManager->getProject($template_id);
        if (!$template_group || !is_object($template_group) || $template_group->isError()) {
          exit_no_group();
        }

        $em     = EventManager::instance();
        $legacy = array(
            Service::SVN       => true,
            Service::TRACKERV3 => true
        );

        $em->processEvent(self::PROJECT_CREATION_REMOVE_LEGACY_SERVICES, array(
            'template'              => $template_group,
            'project_creation_data' => &$data,
            'use_legacy_services'   => &$legacy
        ));

        $this->activateServicesFromTemplate($group, $template_group, $data, $legacy);
        $this->setMessageToRequesterFromTemplate($group_id, $template_id);
        $this->initForumModuleFromTemplate($group_id, $template_id);
        $this->initCVSModuleFromTemplate($group_id, $template_id);

        if ($legacy[Service::SVN] === true) {
            $this->initSVNModuleFromTemplate($group_id, $template_id);
        }

        // Activate other system references not associated with any service
        $this->reference_manager->addSystemReferencesWithoutService($template_id, $group_id);

        //Copy ugroups
        $ugroup_mapping = array();
        $this->ugroup_duplicator->duplicateOnProjectCreation($template_group, $group_id, $ugroup_mapping);

        $this->initFRSModuleFromTemplate($group, $template_id, $ugroup_mapping);

        if ($data->projectShouldInheritFromTemplate() && $legacy[Service::TRACKERV3]) {
            list($tracker_mapping, $report_mapping) =
                $this->initTrackerV3ModuleFromTemplate($group, $template_group, $ugroup_mapping);
        } else {
            $tracker_mapping = array();
            $report_mapping  = array();
        }
        $this->initWikiModuleFromTemplate($group_id, $template_id);

        //Create project specific references if template is not default site template
        if (!$template_group->isSystem()) {
            $this->reference_manager->addProjectReferences($template_id,$group_id);
        }

        $this->copyEmailOptionsFromTemplate($group_id, $template_id);

        $this->label_dao->duplicateLabelsIfNeededBetweenProjectsId($template_id, $group_id);

        // Raise an event for plugin configuration
        $em->processEvent(Event::REGISTER_PROJECT_CREATION, array(
            'reportMapping'         => $report_mapping, // Trackers v3
            'trackerMapping'        => $tracker_mapping, // Trackers v3
            'ugroupsMapping'        => $ugroup_mapping,
            'group_id'              => $group_id,
            'template_id'           => $template_id,
            'project_creation_data' => $data,
            'legacy_service_usage'  => $legacy,
        ));

        if ($data->projectShouldInheritFromTemplate()) {
            $this->initLayoutFromTemplate($group, $template_group);
        }

        $this->autoActivateProject($group);

        return $group_id;
    }

    /**
     * @return int, the group id created
     */
    private function createGroupEntry($data){
        srand((double)microtime()*1000000);
        $random_num=rand(0,1000000);

        // Make sure default project privacy status is defined. If not
        // then default to "public"
        if (!isset($GLOBALS['sys_is_project_public'])) {
            $GLOBALS['sys_is_project_public'] = 1;
        }
        if (isset($GLOBALS['sys_disable_subdomains']) && $GLOBALS['sys_disable_subdomains']) {
          $http_domain=$GLOBALS['sys_default_domain'];
        } else {
          $http_domain=$data->getUnixName().'.'.$GLOBALS['sys_default_domain'];
        }

        $access = $data->getAccess();

        $type = self::$TYPE_PROJECT;
        if ($data->isTest()) {
            $type = self::$TYPE_TEST;
        } elseif ($data->isTemplate()) {
            $type = self::$TYPE_TEMPLATE;
        }

        // make group entry
        $insert_data = array(
            'group_name'          => "'". db_es(htmlspecialchars($data->getFullName())) ."'",
            'access'              => "'".$access."'",
            'unix_group_name'     => "'". db_es($data->getUnixName()) ."'",
            'http_domain'         => "'". db_es($http_domain) ."'",
            'status'              => "'P'",
            'unix_box'            => "'shell1'",
            'cvs_box'             => "'cvs1'",
            'short_description'   => "'". db_es(htmlspecialchars($data->getShortDescription())) ."'",
            'register_time'       => time(),
            'rand_hash'           => "'". md5($random_num) ."'",
            'built_from_template' => db_ei($data->getTemplateId()),
            'type'                => db_ei($type)
        );
        $sql = 'INSERT INTO groups('. implode(', ', array_keys($insert_data)) .') VALUES ('. implode(', ', array_values($insert_data)) .')';
        $result=db_query($sql);

        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','upd_fail',array($GLOBALS['sys_email_admin'],db_error())));
            return false;
        } else {
            $group_id = db_insertid($result);
            return $group_id;
        }
    }

    // insert descriptions
    // insert trove categories
    private function setCategories($data, $group_id) {
        $fields_factory = new DescriptionFieldsFactory(new DescriptionFieldsDao());
        $descfieldsinfos = $fields_factory->getAllDescriptionFields();

        for($i=0;$i<sizeof($descfieldsinfos);$i++){
            $desc_id_val = $data->getField($descfieldsinfos[$i]["group_desc_id"]);
            if($desc_id_val !== null && $desc_id_val != ''){
                $sql="INSERT INTO group_desc_value (group_id, group_desc_id, value) VALUES ('".db_ei($group_id)."','".db_ei($descfieldsinfos[$i]["group_desc_id"])."','".db_escape_string(trim($desc_id_val))."')";
                $result=db_query($sql);

                if (!$result) {
                    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
                    exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','ins_desc_fail',array($host,db_error())));
                }
            }
        }

        foreach($data->getTroveData() as $root => $values) {
            foreach($values as $value) {
                db_query("INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,"
                         ."group_id,trove_cat_root) VALUES (". db_ei($value) .",". time() .",". db_ei($group_id) .",". db_ei($root) .")");
            }
        }
    }

    // define a module
    private function initFileModule($group_id){
        $result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".$this->projectManager->getProject($group_id)->getUnixName()."')");
        if (!$result) {
            list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','ins_file_fail',array($host,db_error())));
        }
    }

    // make the current user a project admin as well as admin
    // on all Tuleap services
    private function setProjectAdmin($group_id, PFUser $user) {
        $result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags,project_flags,patch_flags,support_flags,file_flags,wiki_flags,svn_flags,news_flags) VALUES ("
            . $user->getId() . ","
            . $group_id . ","
            . "'A'," // admin flags
            . "2," // bug flags
            . "2," // forum flags
            . "2," // project flags
            . "2," // patch flags
            . "2," // support flags
            . "2," // file_flags
            . "2," // wiki_flags
            . "2," // svn_flags
            . "2)"); // news_flags
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','set_owner_fail',array($GLOBALS['sys_email_admin'],db_error())));
        }

        // clear the user data to take into account this new group.
        $user->clearGroupData();
    }

    private function getServiceInfoQueryForNewProject(array $legacy, $template_id)
    {
        $template_id      = db_ei($template_id);
        $additional_where = '';

        foreach ($legacy as $service_shortname => $legacy_service_usage) {
            if (! $legacy_service_usage) {
                $service_shortname = db_es($service_shortname);
                $additional_where .= " AND short_name <> '$service_shortname'";
            }
        }

        return "SELECT * FROM service WHERE group_id=$template_id AND is_active=1 $additional_where";
    }

    // Activate the same services on $group_id than those activated on
    // $template_group
    private function activateServicesFromTemplate(Project $group, Group $template_group, ProjectCreationData $data, array $legacy)
    {
        $group_id    = $group->getID();
        $template_id = $template_group->getID();
        $sql         = $this->getServiceInfoQueryForNewProject($legacy, $template_id);
        $result      = db_query($sql);

        while ($arr = db_fetch_array($result)) {
            $service_info = $data->getServiceInfo($arr['service_id']);
            if (isset($service_info['is_used'])) {
                 $is_used = $service_info['is_used'];
            } else {
               $is_used = '0';
               if ($arr['short_name'] == 'admin' || $arr['short_name'] == 'summary') {
                   $is_used = '1';
               }
            }

            if(isset($service_info['server_id']) && $service_info['server_id']) {
                $server_id = $service_info['server_id'];
            } else {
                $server_id = 'null';
            }

            if (! $this->service_creator->createService(
                $arr,
                $group_id,
                array(
                    'system' => $template_group->isSystem(),
                    'name' => $template_group->isSystem() ? '' : $template_group->getUnixName(),
                    'id' => $template_id,
                    'is_used' => $is_used,
                    'server_id' => $server_id,
                )
            )) {
                exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_create_service') .'<br>'. db_error());
            }
        }

        $event = new ProjectRegistrationActivateService($group, $template_group, $legacy);
        EventManager::instance()->processEvent($event);
    }

    //Add the import of the message to requester from the parent project if defined
    private function setMessageToRequesterFromTemplate($group_id, $template_id) {
        $dar = $this->projectManager->getMessageToRequesterForAccessProject($template_id);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            $result = $this->projectManager->setMessageToRequesterForAccessProject($group_id, $row['msg_to_requester']);
        } else {
            $result = $this->projectManager->setMessageToRequesterForAccessProject($group_id, 'member_request_delegation_msg_to_requester');
        }
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_msg_to_requester'));
        }
    }

    private function initForumModuleFromTemplate($group_id, $template_id) {
        $sql = "SELECT forum_name, is_public, description FROM forum_group_list WHERE group_id=$template_id ";
        $result=db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $fid = forum_create_forum($group_id,$arr['forum_name'],$arr['is_public'],1,
                      $arr['description'], $need_feedback = false);
            if ($fid != -1) {
                forum_add_monitor($fid, user_getid());
            }
        }
    }

    private function initCVSModuleFromTemplate($group_id, $template_id) {
        $sql = "SELECT cvs_tracker, cvs_watch_mode, cvs_preamble, cvs_is_private FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups
                  SET cvs_tracker='".db_ei($arr['cvs_tracker'])."',
                      cvs_watch_mode='".db_ei($arr['cvs_watch_mode'])."' ,
                      cvs_preamble='".db_escape_string($arr['cvs_preamble'])."',
                      cvs_is_private = ".db_escape_int($arr['cvs_is_private']) ."
                  WHERE group_id = '$group_id'";

        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_cvs_infos'));
        }
    }

    private function initSVNModuleFromTemplate($group_id, $template_id) {
        $current_timestamp = db_escape_int($_SERVER['REQUEST_TIME']);

        $sql = "INSERT INTO svn_accessfile_history (version_number, group_id, version_date)
                VALUES (1, $group_id, $current_timestamp)";

        $result = db_query($sql);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_svn_infos'));
        }

        $sql = "SELECT svn_tracker, svn_preamble, svn_mandatory_ref, svn_commit_to_tag_denied FROM groups WHERE group_id=$template_id ";
        $result = db_query($sql);
        $arr = db_fetch_array($result);
        $query = "UPDATE groups, svn_accessfile_history
                  SET svn_tracker='".db_ei($arr['svn_tracker'])."',
                      svn_mandatory_ref='".db_ei($arr['svn_mandatory_ref'])."',
                      svn_preamble='".db_escape_string($arr['svn_preamble'])."',
                      svn_commit_to_tag_denied='".db_ei($arr['svn_commit_to_tag_denied'])."',
                      svn_accessfile_version_id = svn_accessfile_history.id
                  WHERE groups.group_id = $group_id
                      AND groups.group_id = svn_accessfile_history.group_id";

        $result=db_query($query);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_svn_infos'));
        }
    }

    private function initFRSModuleFromTemplate(Project $project, $template_id, $ugroup_mapping)
    {
        $sql_ugroup_mapping = ' ugroup_id ';
        if (is_array($ugroup_mapping) && count($ugroup_mapping)) {
            $sql_ugroup_mapping = ' CASE ugroup_id ';
            foreach ($ugroup_mapping as $key => $val) {
                $sql_ugroup_mapping .= ' WHEN '. $key .' THEN '. $val;
            }
            $sql_ugroup_mapping .= ' ELSE ugroup_id END ';
        }
        //Copy packages from template project
        $sql  = "SELECT package_id, name, status_id, rank, approve_license FROM frs_package WHERE group_id = $template_id";
        if ($result = db_query($sql)) {
            while ($p_data = db_fetch_array($result)) {
                $template_package_id = $p_data['package_id'];
                $sql = sprintf(
                    "INSERT INTO frs_package(group_id, name, status_id, rank, approve_license) VALUES (%s, '%s', %s, %s, %s)",
                    db_ei($project->getId()),
                    db_escape_string($p_data['name']),
                    db_ei($p_data['status_id']),
                    db_ei($p_data['rank']),
                    db_ei($p_data['approve_license'])
                );
                $rid = db_query($sql);
                if ($rid) {
                    $package_id = db_insertid($rid);
                    $sql = "INSERT INTO permissions(permission_type, object_id, ugroup_id)
                      SELECT permission_type, $package_id, $sql_ugroup_mapping
                      FROM permissions
                      WHERE permission_type = 'PACKAGE_READ'
                        AND object_id = $template_package_id";
                    db_query($sql);
                }
            }
        }

        $this->frs_permissions_creator->duplicate($project, $template_id);
    }

    // Generic Trackers Creation
    private function initTrackerV3ModuleFromTemplate(Group $group, Group $template_group, $ugroup_mapping) {
        $group_id = $group->getID();
        $tracker_mapping = array();
        $report_mapping  = array();
        if (TrackerV3::instance()->available()) {
            $atf = new ArtifactTypeFactory($template_group);
            //$tracker_error = "";
            // Add all trackers from template project (tracker templates) that need to be instanciated for new trackers.
            $res = $atf->getTrackerTemplatesForNewProjects();
            while ($arr_template = db_fetch_array($res)) {
                $ath_temp = new ArtifactType($template_group,$arr_template['group_artifact_id']);
                $report_mapping_for_this_tracker = array();
                $new_at_id = $atf->create($group_id,$template_group->getID(),$ath_temp->getID(),db_escape_string($ath_temp->getName()),db_escape_string($ath_temp->getDescription()),$ath_temp->getItemName(),$ugroup_mapping,$report_mapping_for_this_tracker);
                if ( !$new_at_id ) {
                    $GLOBALS['Response']->addFeedback('error', $atf->getErrorMessage());
                } else {
                    $report_mapping = $report_mapping + $report_mapping_for_this_tracker;
                    $tracker_mapping[$ath_temp->getID()] = $new_at_id;

                    // Copy all the artifacts from the template tracker to the new tracker
                    $ath_new = new ArtifactType($group,$new_at_id);

                    // not now. perhaps one day
                        //if (!$ath_new->copyArtifacts($ath_temp->getID()) ) {
                    //$GLOBALS['Response']->addFeedback('info', $ath_new->getErrorMessage());
                    //}

                    // Create corresponding reference
                    $ref = new Reference(0, // no ID yet
                                         strtolower($ath_temp->getItemName()),
                                         $GLOBALS['Language']->getText('project_reference','reference_art_desc_key'), // description
                                         '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                                         'P', // scope is 'project'
                                         'tracker',  // service short name
                                         ReferenceManager::REFERENCE_NATURE_ARTIFACT,   // nature
                                         '1', // is_used
                                         $group_id);
                    $result = $this->reference_manager->createReference($ref,true); // Force reference creation because default trackers use reserved keywords
               }
            }
        }
        return array($tracker_mapping, $report_mapping);
    }

    // Clone wiki from the template
    private function initWikiModuleFromTemplate($group_id, $template_id) {
        $clone = new WikiCloner($template_id, $group_id);

        // check if the template project has a wiki initialised
        if ($clone->templateWikiExists() and $clone->newWikiIsUsed()){
            //clone wiki.
            $clone->CloneWiki();
        }
    }

    //Create the summary page
    private function initLayoutFromTemplate(Project $new_project, Project $template)
    {
        $this->dashboard_duplicator->duplicate($template, $new_project);
    }

    // Copy Truncated email option
    private function copyEmailOptionsFromTemplate($group_id, $template_id) {
        $sql = "UPDATE groups AS g1
                JOIN groups AS g2
                  ON g2.group_id = ".db_ei($template_id)."
                SET g1.truncated_emails = g2.truncated_emails
                WHERE g1.group_id = ".db_ei($group_id);

        $result = db_query($sql);
        if (!$result) {
            exit_error($GLOBALS['Language']->getText('global','error'),$GLOBALS['Language']->getText('register_confirmation','cant_copy_truncated_emails'));
        }
    }

    //Verify if the approbation of the new project is automatic or not
    private function autoActivateProject($group)
    {
        $auto_approval = ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, 1) ? PROJECT_APPROVAL_BY_ADMIN : PROJECT_APPROVAL_AUTO;

        if ($this->force_activation || $auto_approval == PROJECT_APPROVAL_AUTO) {
            if ($this->send_notifications) {
                $this->projectManager->activate($group);
            } else {
                $this->projectManager->activateWithoutNotifications($group);
            }
        }
    }

}
