<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
require_once __DIR__ . '/../../www/include/service.php';
require_once __DIR__ . '/../../www/forum/forum_utils.php';
require_once __DIR__ . '/../../www/admin/admin_utils.php';
require_once __DIR__ . '/../../www/include/trove.php';
require_once __DIR__ . '/../../common/wiki/lib/WikiCloner.php';

define('PROJECT_APPROVAL_BY_ADMIN', 'P');
define('PROJECT_APPROVAL_AUTO', 'A');

use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\DB\DBFactory;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Project\Admin\Categories\CategoryCollectionConsistencyChecker;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\Categories\TroveSetNodeFacade;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Banner\BannerCreator;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Banner\UserCanEditBannerPermission;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Email\EmailCopier;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\MappingRegistry;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\ProjectCreationDao;
use Tuleap\Project\Registration\ProjectDescriptionMandatoryException;
use Tuleap\Project\Registration\ProjectInvalidFullNameException;
use Tuleap\Project\Registration\ProjectInvalidShortNameException;
use Tuleap\Project\Registration\ProjectRegistrationBaseChecker;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\ProjectRegistrationCheckerAggregator;
use Tuleap\Project\Registration\ProjectRegistrationCheckerBlockErrorSet;
use Tuleap\Project\Registration\ProjectRegistrationPermissionsChecker;
use Tuleap\Project\Registration\ProjectRegistrationRESTChecker;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\ProjectRegistrationXMLChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\StoreProjectInformation;
use Tuleap\Project\Service\ServiceDao;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

/**
 * Manage creation of a new project in the forge.
 *
 * For now, mainly a wrapper for createProject method
 */
class ProjectCreator //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * When a project is created, ask plugins if they replace the usage of legacy core services
     *
     * Parameters:
     *  - template              => (input) Project
     *  - project_creation_data => (output) array
     *  - use_legacy_services   => (output) array
     */
    public const PROJECT_CREATION_REMOVE_LEGACY_SERVICES = 'project_creation_remove_legacy_services';

    public function __construct(
        private readonly ProjectManager $project_manager,
        private readonly ReferenceManager $reference_manager,
        private readonly UserManager $user_manager,
        private readonly UgroupDuplicator $ugroup_duplicator,
        private readonly bool $send_notifications,
        private readonly FRSPermissionCreator $frs_permissions_creator,
        private readonly LicenseAgreementFactory $frs_license_agreement_factory,
        private readonly ProjectDashboardDuplicator $dashboard_duplicator,
        private readonly LabelDao $label_dao,
        private readonly SynchronizedProjectMembershipDuplicator $synchronized_project_membership_duplicator,
        private readonly EventManager $event_manager,
        private readonly FieldUpdator $field_updator,
        private readonly ProjectServiceActivator $project_service_activator,
        private readonly ProjectRegistrationChecker $registration_checker,
        private readonly ProjectCategoriesUpdater $project_categories_updater,
        private readonly EmailCopier $email_copier,
        private readonly StoreProjectInformation $store_project_information,
        private readonly BannerRetriever $banner_retriever,
        private readonly BannerCreator $banner_creator,
        private readonly bool $force_activation = false,
    ) {
    }

    public static function buildSelfByPassValidation(): self
    {
        return self::buildSelf(true, false, new ProjectRegistrationXMLChecker());
    }

    public static function buildSelfDelayActivation(): self
    {
        return self::buildSelf(
            false,
            false,
            new ProjectRegistrationRESTChecker(
                new DefaultProjectVisibilityRetriever(),
                new CategoryCollectionConsistencyChecker(
                    new \TroveCatFactory(new \TroveCatDao())
                ),
                new ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker(
                    new DescriptionFieldsFactory(
                        new DescriptionFieldsDao()
                    )
                )
            )
        );
    }

    public static function buildSelfRegularValidation(): self
    {
        return self::buildSelf(
            (bool) ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, true) === false,
            true,
            new ProjectRegistrationRESTChecker(
                new DefaultProjectVisibilityRetriever(),
                new CategoryCollectionConsistencyChecker(
                    new \TroveCatFactory(new \TroveCatDao())
                ),
                new ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker(
                    new DescriptionFieldsFactory(
                        new DescriptionFieldsDao()
                    )
                )
            )
        );
    }

    private static function buildSelf(
        bool $force_activation,
        bool $send_notifications,
        ProjectRegistrationChecker $registration_checker,
    ): self {
        $ugroup_dao        = new UGroupDao();
        $ugroup_user_dao   = new UGroupUserDao();
        $ugroup_manager    = new UGroupManager();
        $ugroup_binding    = new UGroupBinding($ugroup_user_dao, $ugroup_manager);
        $event_manager     = EventManager::instance();
        $ugroup_duplicator = new Tuleap\Project\UgroupDuplicator(
            $ugroup_dao,
            $ugroup_manager,
            $ugroup_binding,
            MemberAdder::build(ProjectMemberAdderWithoutStatusCheckAndNotifications::build()),
            $event_manager
        );

        $user_manager   = UserManager::instance();
        $widget_factory = new WidgetFactory(
            $user_manager,
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $event_manager
        );

        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $project_dao       = new ProjectDashboardDao($widget_dao);
        $project_retriever = new ProjectDashboardRetriever($project_dao);
        $widget_retriever  = new DashboardWidgetRetriever($widget_dao);

        $duplicator = new ProjectDashboardDuplicator(
            $project_dao,
            $project_retriever,
            $widget_dao,
            $widget_retriever,
            $widget_factory,
            new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao()),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        );

        $service_dao = new ServiceDao();
        $banner_dao  = new BannerDao();

        return new self(
            ProjectManager::instance(),
            ReferenceManager::instance(),
            $user_manager,
            $ugroup_duplicator,
            $send_notifications,
            new Tuleap\FRS\FRSPermissionCreator(
                new Tuleap\FRS\FRSPermissionDao(),
                $ugroup_dao,
                new ProjectHistoryDao()
            ),
            new LicenseAgreementFactory(new LicenseAgreementDao()),
            $duplicator,
            new LabelDao(),
            new SynchronizedProjectMembershipDuplicator(new SynchronizedProjectMembershipDao()),
            $event_manager,
            new FieldUpdator(
                new DescriptionFieldsFactory(new DescriptionFieldsDao()),
                new \Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO(),
                ProjectXMLImporter::getLogger(),
            ),
            new ProjectServiceActivator(
                new ServiceCreator($service_dao),
                $event_manager,
                $service_dao,
                ServiceManager::instance(),
                new ServiceLinkDataBuilder(),
                ReferenceManager::instance()
            ),
            new ProjectRegistrationCheckerBlockErrorSet(
                new ProjectRegistrationPermissionsChecker(
                    new ProjectRegistrationUserPermissionChecker(
                        new \ProjectDao()
                    ),
                ),
                new ProjectRegistrationCheckerAggregator(
                    new ProjectRegistrationBaseChecker(
                        new \Rule_ProjectName(),
                        new \Rule_ProjectFullName(),
                    ),
                    $registration_checker
                )
            ),
            new ProjectCategoriesUpdater(
                new \TroveCatFactory(new TroveCatDao()),
                new ProjectHistoryDao(),
                new TroveSetNodeFacade(),
            ),
            new EmailCopier(),
            new ProjectCreationDao(),
            new BannerRetriever($banner_dao),
            new BannerCreator($banner_dao),
            $force_activation,
        );
    }

    /**
     * protected for testing purpose
     */
    protected function fakeGroupIdIntoHTTPParams($group_id)
    {
        $_REQUEST['group_id']        = $_GET['group_id'] = $group_id;
        $request                     = HTTPRequest::instance();
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
     * @throws RegistrationForbiddenException
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     * @throws Project_Creation_Exception
     */
    protected function createProject(ProjectCreationData $data): Project
    {
        $admin_user = $this->user_manager->getCurrentUser();

        $errors_collection = $this->registration_checker->collectAllErrorsForProjectRegistration(
            $admin_user,
            $data
        );

        foreach ($errors_collection->getErrors() as $error) {
            throw $error;
        }

        $group_id = $this->store_project_information->create($data);

        $this->field_updator->update($data, $group_id);
        $this->initFileModule($group_id);
        $this->setProjectAdmin($group_id, $admin_user);

        // Instanciate all services from the project template that are 'active'
        $group = $this->project_manager->getProject($group_id);
        if ($group->isError()) {
            throw new Project_Creation_Exception('Creation of the project entry has failed');
        }

        $this->project_categories_updater->update($group, $data->getTroveData());

        $this->fakeGroupIdIntoHTTPParams($group_id);

        $template_group = ($data->getBuiltFromTemplateProject()) ? $data->getBuiltFromTemplateProject()->getProject() : null;

        $legacy = [
            Service::SVN       => true,
            Service::TRACKERV3 => true,
        ];

        $this->event_manager->processEvent(self::PROJECT_CREATION_REMOVE_LEGACY_SERVICES, [
            'template'              => $template_group,
            'project_creation_data' => &$data,
            'use_legacy_services'   => &$legacy,
        ]);

        if ($template_group !== null) {
            $this->project_service_activator->activateServicesFromTemplate($group, $template_group, $data, $legacy);
            $this->setMessageToRequesterFromTemplate($group_id, $template_group->getID());
            $this->initForumModuleFromTemplate($group_id, $template_group->getID());

            if ($legacy[Service::SVN] === true) {
                $this->initSVNModuleFromTemplate($group_id, $template_group->getID());
            }

            $template_banner = $this->banner_retriever->getBannerForProject($template_group);
            if ($template_banner !== null) {
                $this->banner_creator->addBanner(
                    new UserCanEditBannerPermission($group),
                    $template_banner->getMessage(),
                );
            }

            // Activate other system references not associated with any service
            $this->reference_manager->addSystemReferencesWithoutService($template_group->getID(), $group_id);

            $this->synchronized_project_membership_duplicator->duplicate((int) $template_group->getID(), $group);
            //Copy ugroups
            /** @var array<int, int> $ugroup_mapping */
            $ugroup_mapping = [];
            $this->ugroup_duplicator->duplicateOnProjectCreation($template_group, $group_id, $ugroup_mapping, $admin_user);

            $this->initFRSModuleFromTemplate($group, $template_group, $ugroup_mapping);

            if ($data->projectShouldInheritFromTemplate() && $legacy[Service::TRACKERV3]) {
                $this->initTrackerV3ModuleFromTemplate($group, $template_group, $ugroup_mapping);
            }
            $this->initWikiModuleFromTemplate($group_id, $template_group->getID());

            //Create project specific references if template is not default site template
            if (! $template_group->isSystem()) {
                $this->reference_manager->addProjectReferences($template_group->getID(), $group_id);
            }

            $this->email_copier->copyEmailOptionsFromTemplate($group_id, (int) $template_group->getID());

            $this->label_dao->duplicateLabelsIfNeededBetweenProjectsId($template_group->getID(), $group_id);

            $mapping_registry = new MappingRegistry($ugroup_mapping);
            $this->event_manager->processEvent(
                new RegisterProjectCreationEvent(
                    $group,
                    $template_group,
                    $mapping_registry,
                    $admin_user,
                    $legacy,
                    $data->projectShouldInheritFromTemplate(),
                )
            );

            if ($data->projectShouldInheritFromTemplate()) {
                $this->initLayoutFromTemplate($group, $template_group, $mapping_registry);
            }

            if ($this->force_activation) {
                $this->autoActivateProject($group);
            }
        }

        return $group;
    }

    /**
     * protected for testing purpose
     */
    protected function initFileModule($group_id)
    {
        $result = db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('" . db_ei($group_id) . "','" . db_es($this->project_manager->getProject($group_id)->getUnixName()) . "')");
        if (! $result) {
            $host = \Tuleap\ServerHostname::rawHostname();
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('register_confirmation', 'ins_file_fail', [$host, db_error()]));
        }
    }

    /**
     * make the current user a project admin as well as admin on all Tuleap services
     * protected for testing purpose
     */
    protected function setProjectAdmin($group_id, PFUser $user)
    {
        $result = db_query('INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags,project_flags,patch_flags,support_flags,file_flags,wiki_flags,svn_flags,news_flags) VALUES ('
            . db_ei($user->getId()) . ','
            . db_ei($group_id) . ','
            . "'A'," // admin flags
            . '2,' // bug flags
            . '2,' // forum flags
            . '2,' // project flags
            . '2,' // patch flags
            . '2,' // support flags
            . '2,' // file_flags
            . '2,' // wiki_flags
            . '2,' // svn_flags
            . '2)'); // news_flags
        if (! $result) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('register_confirmation', 'set_owner_fail', [ForgeConfig::get('sys_email_admin'), db_error()]));
        }

        // clear the user data to take into account this new group.
        $user->clearGroupData();
    }

    /**
     * Add the import of the message to requester from the parent project if defined
     * protected for testing purpose
     */
    protected function setMessageToRequesterFromTemplate($group_id, $template_id)
    {
        $dar = $this->project_manager->getMessageToRequesterForAccessProject($template_id);
        if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
            $row    = $dar->getRow();
            $result = $this->project_manager->setMessageToRequesterForAccessProject($group_id, $row['msg_to_requester']);
        } else {
            $result = $this->project_manager->setMessageToRequesterForAccessProject($group_id, 'member_request_delegation_msg_to_requester');
        }
        if (! $result) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('register_confirmation', 'cant_copy_msg_to_requester'));
        }
    }

    /**
     * protected for testing purpose
     */
    protected function initForumModuleFromTemplate($group_id, $template_id)
    {
        $sql    = 'SELECT forum_name, is_public, description FROM forum_group_list WHERE group_id=' . db_ei($template_id) . ' ';
        $result = db_query($sql);
        while ($arr = db_fetch_array($result)) {
            $fid               = forum_create_forum(
                $group_id,
                $arr['forum_name'],
                $arr['is_public'],
                1,
                $arr['description'],
                $need_feedback = false
            );
            if ($fid != -1) {
                forum_add_monitor($fid, UserManager::instance()->getCurrentUser()->getId());
            }
        }
    }

    /**
     * protected for testing purpose
     */
    protected function initSVNModuleFromTemplate($group_id, $template_id)
    {
        $current_timestamp = db_escape_int($_SERVER['REQUEST_TIME']);

        $sql = 'INSERT INTO svn_accessfile_history (version_number, group_id, version_date)
                VALUES (1, ' . db_ei($group_id) . ", $current_timestamp)";

        $result = db_query($sql);
        if (! $result) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('register_confirmation', 'cant_copy_svn_infos'));
        }

        $sql    = 'SELECT svn_tracker, svn_preamble, svn_mandatory_ref, svn_commit_to_tag_denied FROM `groups` WHERE group_id=' . db_ei($template_id) . ' ';
        $result = db_query($sql);
        $arr    = db_fetch_array($result);
        $query  = "UPDATE `groups`, svn_accessfile_history
                  SET svn_tracker='" . db_ei($arr['svn_tracker']) . "',
                      svn_mandatory_ref='" . db_ei($arr['svn_mandatory_ref']) . "',
                      svn_preamble='" . db_escape_string($arr['svn_preamble']) . "',
                      svn_commit_to_tag_denied='" . db_ei($arr['svn_commit_to_tag_denied']) . "',
                      svn_accessfile_version_id = svn_accessfile_history.id
                  WHERE `groups`.group_id = " . db_ei($group_id) . '
                      AND `groups`.group_id = svn_accessfile_history.group_id';

        $result = db_query($query);
        if (! $result) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('register_confirmation', 'cant_copy_svn_infos'));
        }
    }

    /**
     * protected for testing purpose
     */
    protected function initFRSModuleFromTemplate(Project $project, Project $template_project, $ugroup_mapping)
    {
        $sql_ugroup_mapping = ' ugroup_id ';
        if (is_array($ugroup_mapping) && count($ugroup_mapping)) {
            $sql_ugroup_mapping = ' CASE ugroup_id ';
            foreach ($ugroup_mapping as $key => $val) {
                $sql_ugroup_mapping .= ' WHEN ' . $key . ' THEN ' . $val;
            }
            $sql_ugroup_mapping .= ' ELSE ugroup_id END ';
        }
        //Copy packages from template project
        $packages_mapping = [];
        $sql              = 'SELECT package_id, name, status_id, rank, approve_license FROM frs_package WHERE group_id = ' . db_ei($template_project->getID());
        if ($result = db_query($sql)) {
            while ($p_data = db_fetch_array($result)) {
                $template_package_id = $p_data['package_id'];
                $sql                 = sprintf(
                    "INSERT INTO frs_package(group_id, name, status_id, rank, approve_license) VALUES (%s, '%s', %s, %s, %s)",
                    db_ei($project->getId()),
                    db_escape_string($p_data['name']),
                    db_ei($p_data['status_id']),
                    db_ei($p_data['rank']),
                    db_ei($p_data['approve_license'])
                );
                $rid                 = db_query($sql);
                if ($rid) {
                    $package_id                                   = db_ei(db_insertid($rid));
                    $packages_mapping[(int) $template_package_id] = (int) $package_id;
                    $sql                                          = "INSERT INTO permissions(permission_type, object_id, ugroup_id)
                      SELECT permission_type, $package_id, $sql_ugroup_mapping
                      FROM permissions
                      WHERE permission_type = 'PACKAGE_READ'
                        AND object_id = " . db_ei($template_package_id);
                    db_query($sql);
                }
            }
        }

        $this->frs_permissions_creator->duplicate($project, $template_project->getID());

        $this->frs_license_agreement_factory->duplicate(
            FRSPackageFactory::instance(),
            $project,
            $template_project,
            $packages_mapping
        );
    }

    /**
     * protected for testing purpose
     */
    protected function initTrackerV3ModuleFromTemplate(Group $group, Group $template_group, $ugroup_mapping)
    {
        $group_id = $group->getID();
        if (TrackerV3::instance()->available()) {
            $atf = new ArtifactTypeFactory($template_group);
            //$tracker_error = "";
            // Add all trackers from template project (tracker templates) that need to be instanciated for new trackers.
            $res = $atf->getTrackerTemplatesForNewProjects();
            while ($arr_template = db_fetch_array($res)) {
                $ath_temp                        = new ArtifactType($template_group, $arr_template['group_artifact_id']);
                $report_mapping_for_this_tracker = [];
                $new_at_id                       = $atf->create($group_id, $template_group->getID(), $ath_temp->getID(), db_escape_string($ath_temp->getName()), db_escape_string($ath_temp->getDescription()), $ath_temp->getItemName(), $ugroup_mapping, $report_mapping_for_this_tracker);
                if (! $new_at_id) {
                    $GLOBALS['Response']->addFeedback('error', $atf->getErrorMessage());
                } else {
                    // Copy all the artifacts from the template tracker to the new tracker
                    $ath_new = new ArtifactType($group, $new_at_id);

                    // not now. perhaps one day
                    //if (!$ath_new->copyArtifacts($ath_temp->getID()) ) {
                    //$GLOBALS['Response']->addFeedback('info', $ath_new->getErrorMessage());
                    //}

                    // Create corresponding reference
                    $ref = new Reference(
                        0, // no ID yet
                        strtolower($ath_temp->getItemName()),
                        $GLOBALS['Language']->getText('project_reference', 'reference_art_desc_key'), // description
                        '/tracker/?func=detail&aid=$1&group_id=$group_id', // link
                        'P', // scope is 'project'
                        'tracker',  // service short name
                        ReferenceManager::REFERENCE_NATURE_ARTIFACT,   // nature
                        '1', // is_used
                        $group_id
                    );
                    $this->reference_manager->createReference($ref, true); // Force reference creation because default trackers use reserved keywords
                }
            }
        }
    }

    /**
     * protected for testing purpose
     */
    protected function initWikiModuleFromTemplate($group_id, $template_id)
    {
        $clone = new WikiCloner($template_id, $group_id);

        // check if the template project has a wiki initialised
        if ($clone->templateWikiExists() and $clone->newWikiIsUsed()) {
            //clone wiki.
            $clone->CloneWiki();
        }
    }

    //Create the summary page
    private function initLayoutFromTemplate(Project $new_project, Project $template, MappingRegistry $mapping_registry)
    {
        $this->dashboard_duplicator->duplicate($template, $new_project, $mapping_registry);
    }

    /**
     * Verify if the approbation of the new project is automatic or not
     * protected for testing purpose
     */
    protected function autoActivateProject($group)
    {
        $auto_approval = ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, 1) ? PROJECT_APPROVAL_BY_ADMIN : PROJECT_APPROVAL_AUTO;

        if ($this->force_activation || $auto_approval == PROJECT_APPROVAL_AUTO) {
            if ($this->send_notifications) {
                $this->project_manager->activateWithNotifications($group);
            } else {
                $this->project_manager->activateWithoutNotifications($group);
            }
        }
    }

    /**
     * @throws Project_Creation_Exception
     */
    public function processProjectCreation(ProjectCreationData $data): Project
    {
        return $this->createProject($data);
    }
}
